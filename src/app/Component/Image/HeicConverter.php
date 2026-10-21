<?php

declare(strict_types=1);

namespace App\Component\Image;

use App\Component\Image\Exception\HeicConversionException;
use Nette\Utils\FileSystem;

/**
 * Fotky z iPhonu (HEIC/HEIF) - GD je neumí a prohlížeče kromě Safari je
 * nezobrazí, proto se převádí do PNG. Přednostně přes Imagick (ImageMagick
 * s delegátem heic) - funguje i na hostingu se zakázaným proc_open;
 * jinak přes nástroj z libheif (heif-dec, ve starších verzích heif-convert;
 * Debian: balíček libheif-examples + libheif-plugin-libde265). PNG je
 * bezeztrátové a nenese EXIF orientaci - libheif fotku při dekódování
 * sám narovná (i pod Imagickem).
 */
class HeicConverter
{
    private const Binaries = ['heif-dec', 'heif-convert'];

    /** Značky (ftyp brand) obrázků HEIF; AVIF GD umí sám, proto tu chybí. */
    private const Brands = ['heic', 'heix', 'heim', 'heis', 'hevc', 'hevx', 'hevm', 'hevs', 'mif1', 'msf1'];

    private string|false|null $binary = null;

    private ?bool $imagick = null;

    /**
     * Je soubor HEIC/HEIF? Pozná se podle hlavičky (ftyp), ne přípony.
     */
    public function isHeic(string $path): bool
    {
        $header = @file_get_contents($path, false, null, 0, 12);
        return $header !== false
            && strlen($header) === 12
            && substr($header, 4, 4) === 'ftyp'
            && in_array(substr($header, 8, 4), self::Brands, true);
    }

    /**
     * Je na serveru nástroj pro převod?
     */
    public function isSupported(): bool
    {
        return $this->isImagickSupported() || $this->getBinary() !== null;
    }

    /**
     * Převede HEIC $source do PNG $target.
     * @throws HeicConversionException
     */
    public function convertToPng(string $source, string $target): void
    {
        $binary = $this->getBinary();
        if ($this->isImagickSupported()) {
            try {
                $this->convertWithImagick($source, $target);
                return;
            } catch (HeicConversionException $e) {
                if ($binary === null) {
                    throw $e;
                }
                // zkusí ještě libheif
            }
        }
        if ($binary === null) {
            throw new HeicConversionException('Převod HEIC není na serveru k dispozici (chybí Imagick s podporou HEIC i heif-dec z libheif).');
        }

        // vlastní adresář - soubor s víc obrázky se zapíše jako image-1.png, image-2.png...
        $dir = $target . '.' . bin2hex(random_bytes(4)) . '.d';
        FileSystem::createDir($dir);
        try {
            // přípona určuje výstupní formát
            [$exitCode, $output] = $this->run([$binary, $source, $dir . '/image.png']);
            $png = is_file($dir . '/image.png')
                ? $dir . '/image.png'
                : $dir . '/image-' . ($this->getPrimaryImageIndex($binary, $source) + 1) . '.png';
            if ($exitCode !== 0 || !is_file($png)) {
                throw new HeicConversionException("Převod HEIC selhal ({$binary}, kód {$exitCode}): " . trim($output));
            }
            FileSystem::rename($png, $target);
        } finally {
            FileSystem::delete($dir);
        }
    }

    /**
     * Imagick čte jen hlavní (primary) obrázek souboru, už otočený podle irot -
     * proto se neotáčí znovu a EXIF (orientace) se zahodí.
     * @throws HeicConversionException
     */
    private function convertWithImagick(string $source, string $target): void
    {
        try {
            $image = new \Imagick($source);
            $image->setIteratorIndex(0);
            $image->stripImage();
            $image->setImageFormat('png');
            $image->writeImage('png:' . $target);
            $image->clear();
        } catch (\ImagickException $e) {
            FileSystem::delete($target);
            throw new HeicConversionException('Převod HEIC přes Imagick selhal: ' . $e->getMessage(), previous: $e);
        }
    }

    private function isImagickSupported(): bool
    {
        return $this->imagick ??= extension_loaded('imagick') && \Imagick::queryFormats('HEIC') !== [];
    }

    /**
     * Pořadí hlavního (primary) obrázku v souboru - ve stejném pořadí je
     * heif-dec zapisuje. Když ho nejde zjistit, bere se první.
     */
    private function getPrimaryImageIndex(string $binary, string $source): int
    {
        $info = dirname($binary) . DIRECTORY_SEPARATOR . 'heif-info';
        if (!@is_executable($info)) {
            return 0;
        }
        [, $output] = $this->run([$info, $source]);
        preg_match_all('~^image: .*$~m', $output, $images);
        foreach ($images[0] as $index => $line) {
            if (str_ends_with(rtrim($line), 'primary')) {
                return $index;
            }
        }
        return 0;
    }

    /**
     * @param list<string> $command
     * @return array{int, string} návratový kód a výstup
     * @throws HeicConversionException
     */
    private function run(array $command): array
    {
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if ($process === false) {
            throw new HeicConversionException("Nepodařilo se spustit {$command[0]}.");
        }
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $output];
    }

    /**
     * Převede HEIC do dočasného PNG, předá jeho cestu $callback a PNG smaže.
     * @template T
     * @param callable(string): T $callback
     * @return T
     * @throws HeicConversionException
     */
    public function withPng(string $source, string $tempDir, callable $callback): mixed
    {
        $png = $tempDir . '/heic_' . bin2hex(random_bytes(8)) . '.png';
        try {
            $this->convertToPng($source, $png);
            return $callback($png);
        } finally {
            FileSystem::delete($png);
        }
    }

    private function getBinary(): ?string
    {
        if ($this->binary === null) {
            $this->binary = $this->findBinary() ?? false;
        }
        return $this->binary === false ? null : $this->binary;
    }

    private function findBinary(): ?string
    {
        if (!function_exists('proc_open')) {
            return null;
        }
        $dirs = explode(PATH_SEPARATOR, (string) getenv('PATH'));
        // PHP-FPM/Apache mívá PATH prázdnou nebo osekanou
        array_push($dirs, '/usr/bin', '/usr/local/bin', '/bin');
        foreach (self::Binaries as $name) {
            foreach (array_unique(array_filter($dirs)) as $dir) {
                $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $name;
                if (@is_file($path) && @is_executable($path)) {
                    return $path;
                }
            }
        }
        return null;
    }
}
