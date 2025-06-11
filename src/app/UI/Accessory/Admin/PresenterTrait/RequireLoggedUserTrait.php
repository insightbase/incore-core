<?php

namespace App\UI\Accessory\Admin\PresenterTrait;

use App\Model\Admin\User;
use App\Model\Entity\UserEntity;
use Nette\Bridges\SecurityHttp\SessionStorage;
use Nette\Database\Table\ActiveRow;

trait RequireLoggedUserTrait
{
    /**
     * @var UserEntity
     */
    protected ActiveRow $loggedUser;

    public function injectRequireLoggedUser(User $userModel): void
    {
        $this->onStartup[] = function () use ($userModel) {
            $storage = $this->getUser()->getStorage();
            if ($storage instanceof SessionStorage) {
                $storage->setNamespace('admin');
            }
            if (!$this->getUser()->isLoggedIn()) {
                $this->redirect('Sign:login', ['storeRequest' => $this->storeRequest()]);
            }
            $this->loggedUser = $userModel->get($this->getUser()->getId());
        };
    }
}
