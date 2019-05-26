<?php
namespace Bs\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class UserPass extends \Tk\Console\Console
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('userPass')
            ->setAliases(array('pwd'))
            ->addArgument('username', InputArgument::REQUIRED, 'A valid username.')
            ->addArgument('password', InputArgument::REQUIRED, 'A valid password for the user.')
            ->addArgument('institutionId', InputArgument::OPTIONAL, 'A valid institutionId if username is not unique.', null)
            //->addArgument('roleId', InputArgument::OPTIONAL, 'A valid institutionId if username is not unique.', 5)
            ->setDescription('Set a users new password')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $config = \App\Config::getInstance();
        $db = $config->getDb();
        
        $options = array();
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $institutionId = $input->getArgument('institutionId');

        $mapper = $config->getUserMapper();

        $filter = array(
            'username' => $username
        );
        if ($institutionId !== null) {
            $filter['institutionId'] = $institutionId;
        }

        $userList = $mapper->findFiltered($filter);
        if (count($userList) > 1) {
            if ($institutionId == 0) {
                $this->writeError('Please supply an institution ID as the username is not unique');
                return;
            }
            $this->writeError('Error: user is not unique: ' . count($userList) . ' users found.');
            return;
        }  else if (!count($userList)) {
            $this->writeError('Error: No valid user found.');
            return;
        }

        /** @var \Bs\Db\User $user */
        $user = $userList->current();
        $user->setNewPassword($password);
        $user->save();


        //vd($user);

    }

}
