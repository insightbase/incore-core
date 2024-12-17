<?php

namespace App\Console;

use App\Model\Enum\RoleEnum;
use App\Model\Role;
use App\Model\User;
use Nette\Security\Passwords;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'user:createSuperAdmin', description: 'Create super admin user')]
class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private readonly User      $userModel,
        private readonly Role      $roleModel,
        private readonly Passwords $passwords,
    )
    {
        parent::__construct();
    }

    protected function configure():void
    {
        $this->addArgument('firstname', InputArgument::REQUIRED, "User's firstname");
        $this->addArgument('lastname', InputArgument::REQUIRED, "User's lastname");
        $this->addArgument('email', InputArgument::REQUIRED, "User's email");
        $this->addArgument('password', InputArgument::REQUIRED, "User's password");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $role = $this->roleModel->findBySystemName(RoleEnum::SUPER_ADMIN->value);
        if($role === null) {
            $output->writeln(sprintf('<error>Role "%s" not found in database</error>', RoleEnum::SUPER_ADMIN->value));
        }

        $this->userModel->insert([
            'firstname' => $input->getArgument('firstname'),
            'lastname' => $input->getArgument('lastname'),
            'email' => $input->getArgument('email'),
            'password' => $this->passwords->hash($input->getArgument('password')),
            'role_id' => $role->id,
        ]);

        $output->writeln('<info>User created</info>');

        return self::SUCCESS;
    }
}