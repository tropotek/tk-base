<?php
namespace Bs\Console;

use App\Db\User;
use Bs\Auth;
use Bs\Factory;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;
use Tk\Config;

class CreateAdmin extends Console
{

    protected function configure()
    {
        $this->setName('create-admin')
            ->setAliases(['adm'])
            ->addOption('ignore-pwd-policy', 'i', InputOption::VALUE_NEGATABLE, 'Ignore password policy when setting password', false)
            ->addArgument('username', InputArgument::REQUIRED, 'A valid username.')
            ->setDescription('Create a new admin user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');

        $user = Auth::findByUsername($username);
        if ($user instanceof Auth) {
            $this->writeError('Error: User with that username already exists.');
            return self::FAILURE;
        }

        $email = $username . '@' . Config::getHostname();
        $first = true;
        do {
            if (!$first) {
                $this->writeError("Invalid Email: \n");
            }
            $q = new Question('Enter user email['.$email.']: ', $email);
            $q->setTrimmable(true);

            /** @phpstan-ignore-next-line */
            $email = $this->getHelper('question')->ask($input, $output, $q);
            $first = false;
        } while(!filter_var($email, FILTER_VALIDATE_EMAIL));

        $errors = [];
        do {
            if (count($errors)) {
                $this->writeError("Invalid Password: \n  - " . implode("\n  - ", $errors));
            }
            $q = new Question('Enter the new password: ', '');
            $q->setHidden(true);
            $q->setTrimmable(true);

            /** @phpstan-ignore-next-line */
            $pass = $this->getHelper('question')->ask($input, $output, $q);
            if (!$input->getOption('ignore-pwd-policy')) {
                $errors = Auth::validatePassword($pass);
            }
        } while(!empty($errors));

        $first = true;
        do {
            if (!$first) {
                $this->writeError("Passwords do not match.\n");
            }
            $q = new Question('Confirm new password: ', '');
            $q->setHidden(true);
            $q->setTrimmable(true);

            /** @phpstan-ignore-next-line */
            $passConf = $this->getHelper('question')->ask($input, $output, $q);
            $first = false;
        } while($pass != $passConf);

        Factory::instance()->createNewUser($username, $email, $pass, Auth::PERM_ADMIN, User::TYPE_STAFF);

        $this->writeGreen('New admin user created.');
        return self::SUCCESS;
    }

}
