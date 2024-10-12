<?php
namespace Bs\Console;

use Bs\Auth;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

class Password extends Console
{

    protected function configure()
    {
        $this->setName('password')
            ->setAliases(array('pwd'))
            ->addArgument('username', InputArgument::REQUIRED, 'A valid username.')
            ->setDescription('Set a users new password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');

        $user = Auth::findByUsername($username);
        if (!$user) {
            $this->writeError('Error: No valid user found.');
            return self::FAILURE;
        }

        $errors = [];
        do {
            if (count($errors)) {
                $this->writeError("Invalid Password: \n  - " . implode("\n  - ", $errors));
            }
            $q = new Question('Enter the new password: ', '');

            /** @phpstan-ignore-next-line */
            $pass = $this->getHelper('question')->ask($input, $output, $q);
        } while($errors = Auth::validatePassword($pass));

        do {
            if (count($errors)) {
                $this->writeError("Passwords do not match.\n");
            }
            $q = new Question('Confirm new password: ', '');
            /** @phpstan-ignore-next-line */
            $passConf = $this->getHelper('question')->ask($input, $output, $q);
        } while($pass != $passConf);

        $this->writeGreen('Password for user \''.$username.'\' updated');
        $user->password = Auth::hashPassword($pass);
        $user->save();

        return self::SUCCESS;
    }

}
