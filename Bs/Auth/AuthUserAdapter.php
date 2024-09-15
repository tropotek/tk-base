<?php
namespace Bs\Auth;

use Bs\Db\User;
use Tk\Auth\Adapter\AdapterInterface;
use Tk\Auth\Result;

/**
 * A Tk lib table authenticator adaptor
 *
 * This adaptor requires that the password and username are submitted in a POST request
 * @see https://www.php.net/manual/en/function.password-hash.php
 */
class AuthUserAdapter extends AdapterInterface
{

    public function authenticate(): Result
    {
        // get values from a post request only
        $username = trim($_POST['username']) ?? '';
        $password = trim($_POST['password']) ?? '';

        if (!$username || !$password) {
            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $username, 'No username or password.');
        }

        try {
            $user = User::findByUsername($username);
            if ($user && password_verify($password, $user->password)) {
                return new Result(Result::SUCCESS, $username);
            }
        } catch (\Exception $e) {
            \Tk\Log::notice($e->__toString());
        }
        return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, $username, 'Invalid username or password.');
    }

}
