<?php

namespace App\Core\Admin\Impersonation;

use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Core\Admin\Authenticator;
use App\Model\Admin\User as UserModel;
use App\Model\Entity\UserEntity;
use App\Model\Enum\RoleEnum;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Database\Table\ActiveRow;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;

/**
 * Přihlášení super admina jako jiný uživatel a návrat zpět. ID super admina je v samostatné sekci
 * session - identitu Authenticator při každém požadavku skládá znovu z DB, takže by se v ní ztratilo.
 */
final class ImpersonationFacade
{
    private const string Section = 'impersonation';
    private const string ImpersonatorKey = 'impersonatorId';

    public function __construct(
        private readonly User $user,
        private readonly Session $session,
        private readonly UserModel $userModel,
        private readonly Authenticator $authenticator,
        private readonly LogFacade $logFacade,
    ) {}

    /**
     * @param UserEntity $target
     */
    public function canImpersonate(ActiveRow $target): bool
    {
        // impersonace se nevnořuje
        if (!$this->user->isLoggedIn() || $this->isActive()) {
            return false;
        }

        return ImpersonationPolicy::canImpersonate(
            array_values(array_filter($this->user->getRoles(), 'is_string')),
            (int) $this->user->getId(),
            $target->id,
            $target->role->system_name,
        );
    }

    /**
     * @throws BadRequestException
     * @throws ForbiddenRequestException
     */
    public function start(int $userId): void
    {
        $target = $this->userModel->get($userId);
        if (null === $target) {
            throw new BadRequestException('', 404);
        }
        if (!$this->canImpersonate($target)) {
            throw new ForbiddenRequestException();
        }
        $identity = $this->authenticator->wakeupIdentity(new SimpleIdentity($target->id));
        if (null === $identity) {
            throw new BadRequestException('', 404);
        }

        $impersonatorId = (int) $this->user->getId();
        // log ještě pod super adminem
        $this->logFacade->create(LogActionEnum::ImpersonationStarted, 'user', $target->id);
        $this->user->login($identity);
        $this->getSection()->set(self::ImpersonatorKey, $impersonatorId);
    }

    /** Vrátí super admina na jeho účet; když už super adminem není (nebo neexistuje), odhlásí úplně. */
    public function stop(): void
    {
        $impersonatorId = $this->getImpersonatorId();
        if (null === $impersonatorId || !$this->user->isLoggedIn()) {
            // odhlášená session (smazaný cíl, vypršení) nesmí návratem získat účet super admina bez hesla
            $this->clear();

            return;
        }
        $impersonatedId = (int) $this->user->getId();
        $this->clear();

        $identity = $this->authenticator->wakeupIdentity(new SimpleIdentity($impersonatorId));
        if (null === $identity || !in_array(RoleEnum::SUPER_ADMIN->value, $identity->getRoles(), true)) {
            $this->user->logout(true);

            return;
        }

        $this->user->login($identity);
        $this->logFacade->create(LogActionEnum::ImpersonationEnded, 'user', $impersonatedId);
    }

    /** Zapomene probíhající impersonaci (odhlášení, nové přihlášení formulářem). */
    public function clear(): void
    {
        $this->getSection()->remove();
    }

    public function isActive(): bool
    {
        return $this->user->isLoggedIn() && null !== $this->getImpersonatorId();
    }

    /**
     * @return ?UserEntity uživatel, za kterého se super admin právě vydává
     */
    public function getImpersonatedUser(): ?ActiveRow
    {
        return $this->isActive() ? $this->userModel->get((int) $this->user->getId()) : null;
    }

    private function getImpersonatorId(): ?int
    {
        $id = $this->getSection()->get(self::ImpersonatorKey);

        return is_int($id) ? $id : null;
    }

    private function getSection(): SessionSection
    {
        return $this->session->getSection(self::Section);
    }
}
