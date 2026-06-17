<?php

namespace App\UI\Accessory\Admin\Form\Controls\EditorJs;

use App\Model\Admin\Setting;
use Nette;
use Nette\Forms\Controls\TextInput;
use Stringable;

class EditorJsInput extends TextInput
{
    /**
     * @var ?EditorJsTypeEnum[]
     */
    public ?array $showType = null;

    public function __construct(
        private readonly Nette\Application\LinkGenerator $linkGenerator,
        private readonly Setting                         $settingModel,
        Stringable|string|null                           $label = null, ?int $maxLength = null)
    {
        parent::__construct($label, $maxLength);
    }

    public function getControl(): Nette\Utils\Html
    {
        $control = parent::getControl();

        if($this->showType === null){
            $this->showType = $this->resolveEnabledTypes();
        }

        $control->setAttribute('data-type', implode(';', Nette\Utils\Arrays::map($this->showType, fn($value) => $value->value)));
        $control->setAttribute('data-upload-url', $this->linkGenerator->link('Admin:EditorJs:upload'));
        return $control;
    }

    /**
     * Resolves the globally enabled plugin types from settings.
     * Null stored value means all plugins are enabled (default).
     *
     * @return EditorJsTypeEnum[]
     */
    private function resolveEnabledTypes(): array
    {
        $stored = $this->settingModel->getDefault()?->editor_js_enabled_types ?? null;
        if ($stored === null) {
            return EditorJsTypeEnum::cases();
        }

        $enabledValues = array_filter(explode(';', $stored));

        return array_values(array_filter(
            EditorJsTypeEnum::cases(),
            fn(EditorJsTypeEnum $type): bool => in_array($type->value, $enabledValues, true),
        ));
    }
}