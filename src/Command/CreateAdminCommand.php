<?php

namespace App\Command;

use App\Admin\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED)
            ->addArgument('password', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = mb_strtolower(trim((string) $input->getArgument('email')));
        $password = (string) $input->getArgument('password');

        $existing = $this->em
            ->getRepository(AdminUser::class)
            ->findOneBy(['email' => $email]);

        if ($existing) {
            $output->writeln('<error>Er bestaat al een admin user met dit e-mailadres.</error>');
            return Command::FAILURE;
        }

        $user = new AdminUser();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $password)
        );

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('<info>Admin user aangemaakt:</info> ' . $email);

        return Command::SUCCESS;
    }
}