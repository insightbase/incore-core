<?php

namespace App\UI\MyAccount\Form;

use App\Component\Translator\Translator;
use App\Model\User;
use App\UI\Accessory\Form\Form;
use App\UI\Accessory\Form\FormFactory;
use Nette\Forms\Controls\BaseControl;

class MyAccountFormFactory
{
    public function __construct(
        private FormFactory $formFactory,
        private Translator $translator,
        private User $userModel,
        private \Nette\Security\User $userSecurity,
    )
    {
    }

    public function createChangePassword():Form
    {
        $form = $this->formFactory->create();
        $password = $form->addPassword('password', $this->translator->translate('input_newPassword'))
            ->setHtmlAttribute('autocomplete', 'new-password')
            ->setRequired();
        $form->addPassword('password1', $this->translator->translate('input_newPasswordCheck'))
            ->setHtmlAttribute('autocomplete', 'new-password')
            ->addRule($form::Equal, $this->translator->translate('error_bothPasswordsMustBeSame'), $password)
            ->setRequired()
            ->setOmitted()
        ;
        $form->addSubmit('send', $this->translator->translate('submit_changePassword'));
        return $form;
    }

    public function create():Form
    {
        $user = $this->userModel->get($this->userSecurity->getId());

        $form = $this->formFactory->create();

        $form->addText('firstname', $this->translator->translate('input_firstName'))
            ->setRequired();
        $form->addText('lastname', $this->translator->translate('input_lastName'))
            ->setRequired();
        $form->addEmail('email', $this->translator->translate('input_email'))
            ->setRequired()
            ->addRule([$this, 'validateEmail'], $this->translator->translate('error_emailAlreadyExists'));
        ;
        $form->addDropzone('avatar', $this->translator->translate('input_avatar'))
            ->setNullable();
        $form->addSubmit('send', $this->translator->translate('input_update'));

        $form->setDefaults([
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'avatar' => $user->avatar,
        ]);

        return $form;
    }

    public function validateEmail(BaseControl $input):bool
    {
        $user = $this->userModel->findByEmail($input->getValue(), $this->userSecurity->getId());
        return $user === null;
    }
}