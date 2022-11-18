<?php
namespace Bs\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tk\Console\Console;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class UserPass extends Console
{

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        // TODO: Implement this for debug mode when we have a user system
        $this->writeError('TODO: This command is under development and is not operational yet!');
        return self::FAILURE;

        $config = $this->getConfig();

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
                return self::FAILURE;
            }
            $this->writeError('Error: user is not unique: ' . count($userList) . ' users found.');
            return self::FAILURE;
        }  else if (!count($userList)) {
            $this->writeError('Error: No valid user found.');
            return self::FAILURE;
        }

        /** @var \Bs\Db\User $user */
        $user = $userList->current();
        $user->setNewPassword($password);
        $user->save();

        return self::SUCCESS;
    }

}
