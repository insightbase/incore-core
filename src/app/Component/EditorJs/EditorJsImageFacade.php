<?php

declare(strict_types=1);

namespace App\Component\EditorJs;

use App\UI\Accessory\ParameterBag;
use Nette\Application\LinkGenerator;
use Nette\Http\FileUpload;
use Nette\Http\Url;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;
use Nette\Utils\ImageColor;
use Nette\Utils\ImageType;

/**
 * Soubory nahrané přes editor.js (www/editor-js). Rastrové obrázky se při
 * uložení zmenší max. na FullHD a převedou do WebP; originál se nikam
 * neukládá. Náhledy pro galerie (www/editor-js/thumb) vznikají líně až při
 * prvním zobrazení - viz Admin:EditorJsThumbnail.
 */
readonly class EditorJsImageFacade
{
    public const DirName = 'editor-js';
    private const ThumbDirName = 'thumb';
    private const MaxWidth = 1920;
    private const MaxHeight = 1080;
    private const ThumbSize = 600;
    private const Quality = 82;
    private const MemoryLimit = 1024 * 1024 * 1024;

    /** GIF záměrně chybí - GD by zahodilo animaci. */
    private const OptimizableTypes = [ImageType::JPEG, ImageType::PNG, ImageType::WEBP, ImageType::BMP];

    public function __construct(
        private ParameterBag  $parameterBag,
        private LinkGenerator $linkGenerator,
    ) {}

    public function getDir(): string
    {
        // ne wwwDir - z konzole ukazuje na bin/
        return $this->parameterBag->rootDir . '/www/' . self::DirName;
    }

    public function getUrl(string $name): string
    {
        return '/' . self::DirName . '/' . $name;
    }

    /**
     * Uloží nahraný soubor a vrátí jeho veřejnou URL.
     */
    public function saveUpload(FileUpload $file): string
    {
        FileSystem::createDir($this->getDir());
        $sanitizedName = $file->getSanitizedName();

        if ($this->isOptimizable($file->getTemporaryFile())) {
            $name = $this->getUniqueName($this->getWebpName($sanitizedName));
            try {
                $this->optimize($file->getTemporaryFile(), $name);
                return $this->getUrl($name);
            } catch (\Throwable) {
                // nepodařilo se zpracovat (např. animované WebP) - uložíme beze změny
                FileSystem::delete($this->getDir() . '/' . $name);
            }
        }

        $name = $this->getUniqueName($sanitizedName);
        $file->move($this->getDir() . '/' . $name);
        return $this->getUrl($name);
    }

    public function isOptimizable(string $path): bool
    {
        $type = Image::detectTypeFromFile($path);
        return $type !== null
            && in_array($type, self::OptimizableTypes, true)
            && Image::isTypeSupported($type)
            && Image::isTypeSupported(ImageType::WEBP);
    }

    /**
     * Potřebuje soubor převést/zmenšit? (Malé WebP už ne, aby se zbytečně
     * nepřekódovávalo.)
     */
    public function needsOptimization(string $path): bool
    {
        if (!$this->isOptimizable($path)) {
            return false;
        }
        $type = Image::detectTypeFromFile($path, $width, $height);
        return $type !== ImageType::WEBP || $width > self::MaxWidth || $height > self::MaxHeight;
    }

    /**
     * Zmenší obrázek $source max. na FullHD a uloží ho jako WebP do
     * editor-js/$targetName. $source může být i sám cíl.
     */
    public function optimize(string $source, string $targetName): void
    {
        $image = $this->loadImage($source);
        $image->resize(self::MaxWidth, self::MaxHeight, Image::ShrinkOnly);
        $this->saveWebp($image, $this->getDir() . '/' . $targetName);
    }

    /**
     * Název souboru v editor-js, pokud URL míří přímo do něj, jinak null.
     */
    public function getLocalName(string $url): ?string
    {
        $prefix = '/' . self::DirName . '/';
        $path = (string) parse_url($url, PHP_URL_PATH);
        if (!str_starts_with($path, $prefix)) {
            return null;
        }

        $name = rawurldecode(substr($path, strlen($prefix)));
        return $this->isLocalFile($name) ? $name : null;
    }

    /**
     * Je $name soubor přímo v editor-js (ne v podadresáři, ne mimo něj)?
     */
    public function isLocalFile(string $name): bool
    {
        return $name !== ''
            && !str_contains($name, '/')
            && !str_contains($name, '\\')
            && !str_starts_with($name, '.')
            && is_file($this->getDir() . '/' . $name);
    }

    /**
     * URL náhledu obrázku pro galerii. Dokud náhled neexistuje, míří na
     * Admin:EditorJsThumbnail, který ho při prvním zobrazení vygeneruje;
     * u cizích URL a souborů, které nejdou zmenšit, vrátí původní URL.
     */
    public function getGalleryThumbnailUrl(string $url): string
    {
        $name = $this->getLocalName($url);
        if ($name === null) {
            return $url;
        }
        if ($this->hasThumbnail($name)) {
            return $this->getThumbnailUrl($name);
        }
        if (!$this->isOptimizable($this->getDir() . '/' . $name)) {
            return $url;
        }
        // relativně - LinkGenerator mimo HTTP požadavek (konzole) zná jen example.com
        $link = new Url($this->linkGenerator->link('Admin:EditorJsThumbnail:default', ['name' => $name]));
        return $link->getPath() . '?' . $link->getQuery();
    }

    public function hasThumbnail(string $name): bool
    {
        return is_file($this->getThumbnailPath($name));
    }

    public function getThumbnailUrl(string $name): string
    {
        return $this->getUrl(self::ThumbDirName . '/' . $this->getThumbnailName($name));
    }

    public function getThumbnailPath(string $name): string
    {
        return $this->getDir() . '/' . self::ThumbDirName . '/' . $this->getThumbnailName($name);
    }

    /**
     * Vygeneruje náhled obrázku editor-js/$name, pokud ještě neexistuje.
     */
    public function createThumbnail(string $name): void
    {
        $path = $this->getThumbnailPath($name);
        if (is_file($path)) {
            return;
        }

        $image = $this->loadImage($this->getDir() . '/' . $name);
        $image->resize(self::ThumbSize, self::ThumbSize, Image::ShrinkOnly);
        FileSystem::createDir(dirname($path));
        $this->saveWebp($image, $path);
    }

    public function deleteThumbnail(string $name): void
    {
        FileSystem::delete($this->getThumbnailPath($name));
    }

    public function getWebpName(string $name): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        return ($base !== '' ? $base : 'image') . '.webp';
    }

    /**
     * Vrátí název, který v adresáři ještě neexistuje (přidá -1, -2, ...).
     */
    public function getUniqueName(string $name): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $suffix = $extension !== '' ? '.' . $extension : '';

        $candidate = $name;
        for ($i = 1; file_exists($this->getDir() . '/' . $candidate); $i++) {
            $candidate = $base . '-' . $i . $suffix;
        }
        return $candidate;
    }

    /**
     * Náhled je vždy WebP; u ještě nepřevedených souborů (foto.jpeg) se přípona
     * přidává (foto.jpeg.webp), aby se nepotkal s náhledem jiného foto.webp.
     */
    private function getThumbnailName(string $name): string
    {
        return str_ends_with(strtolower($name), '.webp') ? $name : $name . '.webp';
    }

    private function loadImage(string $path): Image
    {
        $this->raiseMemoryLimit();
        $image = Image::fromFile($path, $type);
        if ($type === ImageType::JPEG) {
            $this->applyExifOrientation($image, $path);
        }
        return $image;
    }

    /**
     * Přes dočasný soubor - kvůli přepisu samotného zdroje i souběžným
     * požadavkům na stejný náhled.
     */
    private function saveWebp(Image $image, string $path): void
    {
        $image->alphaBlending(false);
        $image->saveAlpha(true);

        $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $image->save($tmp, self::Quality, ImageType::WEBP);
        FileSystem::rename($tmp, $path);
    }

    /**
     * GD ignoruje EXIF orientaci a WebP ji neponese - fotky z foťáku/mobilu
     * na výšku by skončily otočené, proto je narovnáme rovnou v pixelech.
     */
    private function applyExifOrientation(Image $image, string $path): void
    {
        switch ($this->readExifOrientation($path)) {
            case 2:
                $image->flip(IMG_FLIP_HORIZONTAL);
                break;
            case 3:
                $image->rotate(180, ImageColor::rgb(0, 0, 0));
                break;
            case 4:
                $image->flip(IMG_FLIP_VERTICAL);
                break;
            case 5:
                $image->flip(IMG_FLIP_HORIZONTAL);
                $image->rotate(90, ImageColor::rgb(0, 0, 0));
                break;
            case 6:
                $image->rotate(-90, ImageColor::rgb(0, 0, 0));
                break;
            case 7:
                $image->flip(IMG_FLIP_HORIZONTAL);
                $image->rotate(-90, ImageColor::rgb(0, 0, 0));
                break;
            case 8:
                $image->rotate(90, ImageColor::rgb(0, 0, 0));
                break;
        }
    }

    /**
     * Přečte tag Orientation (0x0112) z IFD0 v EXIFu JPEGu. Vlastní parser,
     * protože rozšíření exif nemusí být na serveru k dispozici.
     */
    private function readExifOrientation(string $path): int
    {
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            return is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        }

        $data = @file_get_contents($path, false, null, 0, 256 * 1024);
        if ($data === false || !str_starts_with($data, "\xFF\xD8")) {
            return 1;
        }

        $offset = 2;
        $length = strlen($data);
        while ($offset + 4 <= $length && $data[$offset] === "\xFF") {
            $marker = ord($data[$offset + 1]);
            $segmentLength = unpack('n', $data, $offset + 2)[1];
            if ($marker === 0xE1 && substr($data, $offset + 4, 6) === "Exif\0\0") {
                return $this->readTiffOrientation(substr($data, $offset + 10, $segmentLength - 8));
            }
            if ($marker === 0xDA) { // začátek obrazových dat - EXIF už nebude
                break;
            }
            $offset += 2 + $segmentLength;
        }
        return 1;
    }

    private function readTiffOrientation(string $tiff): int
    {
        if (strlen($tiff) < 8) {
            return 1;
        }
        $littleEndian = substr($tiff, 0, 2) === 'II';
        $short = $littleEndian ? 'v' : 'n';
        $long = $littleEndian ? 'V' : 'N';

        $ifdOffset = unpack($long, $tiff, 4)[1];
        if ($ifdOffset + 2 > strlen($tiff)) {
            return 1;
        }
        $count = unpack($short, $tiff, $ifdOffset)[1];
        for ($i = 0; $i < $count; $i++) {
            $entry = $ifdOffset + 2 + $i * 12;
            if ($entry + 12 > strlen($tiff)) {
                break;
            }
            if (unpack($short, $tiff, $entry)[1] === 0x0112) {
                $value = unpack($short, $tiff, $entry + 8)[1];
                return $value >= 1 && $value <= 8 ? $value : 1;
            }
        }
        return 1;
    }

    /**
     * Rozbalení velké fotky (např. 16 MB JPEG) do GD si vezme stovky MB.
     */
    private function raiseMemoryLimit(): void
    {
        $limit = trim((string) ini_get('memory_limit'));
        if ($limit === '-1') {
            return;
        }
        $bytes = (int) $limit;
        $bytes *= match (strtolower(substr($limit, -1))) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };
        if ($bytes < self::MemoryLimit) {
            ini_set('memory_limit', (string) self::MemoryLimit);
        }
    }
}
