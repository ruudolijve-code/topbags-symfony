<?php

namespace App\Command;

use App\Admin\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:admin:enable-2fa',
    description: 'Enable Google Authenticator 2FA for an admin user'
)]
class EnableTwoFactorCommand extends Command
{
    public function __construct(
        private AdminUserRepository $adminUserRepository,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Admin email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');

        $user = $this->adminUserRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $output->writeln('<error>User not found</error>');
            return Command::FAILURE;
        }

        $totp = TOTP::create();
        $totp->setLabel($email);
        $totp->setIssuer('Topbags Admin');

        $secret = $totp->getSecret();

        $user->setGoogleAuthenticatorSecret($secret);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('');
        $output->writeln('<info>2FA enabled for '.$email.'</info>');
        $output->writeln('');
        $output->writeln('Secret: '.$secret);
        $output->writeln('');
        $output->writeln('Scan QR with Google Authenticator:');
        $output->writeln($totp->getProvisioningUri());
        $output->writeln('');

        return Command::SUCCESS;
    }
}