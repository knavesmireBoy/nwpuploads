<?php

namespace PoloAfrica\Controllers;

use \Ninja\DatabaseTable;

include_once __DIR__ . '/../config.php';
include_once FUNCTIONS;

class User
{
    private $permissions;

    private function exit($subject, $info = '')
    {
        //$subject would be potentially empty IF AND ONLY IF directly amending the url
        if (isset($_SESSION['username'])) {
            $subject = $subject ?? $this->table->find('email', $_SESSION['username'])[0];
        }
        $location = empty($subject) ? REG : ($subject->hasPermission(\PoloAfrica\Entity\User::ACCOUNT_EDITOR) ? USER_LIST : BADMINTON);
        reLocate($location . $info, '../../');
    }

    protected function persist($record, $route = 'edit')
    {
        $unset = doSetCookie(false);
        $set = doSetCookie(true);
        try {
            $unset('error');
            $e = $this->table->save($record);
        } catch (\Exception $e) {
            $msg = 'Error saving record: ' . $e->getMessage();
            $set('error', $msg);
            reLocate(USER_EDIT . $record['id'] ?? '');
        }
        return $e;
    }

    protected function fetch($t, $prop, $val, ...$rest)
    {
        $ret = [];
        if ($val) { //safeguard against missing values
            if (strtoupper($t) === $t) {
                $t = strtolower($t);
                $ret = $this->{$t}->find($prop, $val, null, 0, 0, \PDO::FETCH_ASSOC);
            } else {
                $ret = $this->{$t}->find($prop, $val, ...$rest);
            }
        }
        return empty($ret) ? null : $ret[0];
    }

    private function hasPermission(int $permission)
    {
        return $this->permissions & $permission;
    }

    protected function getAccess($i)
    {
        //2 'Content Editors' //4 'Photo Editors' 
        $lib = [1 => 'Registered Users', 2 => 'Content Editors', 4 => 'Photo Editors', 8 => 'Chief Editors', 64 => 'Senior Management'];
        return isset($lib[$i]) ? $lib[$i] : 'Account Administrators';
    }

    private function authorise($pass, $ownerid, $userid)
    {
        return $pass || equals($ownerid, $userid);
    }

    private function validateEmail($email, $header = '')
    {
        $errors = [];
        if (empty($email)) {
            $errors[] = 'Email cannot be blank';
        } else if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Invalid email address';
        } else {
            $email = strtolower($email);
            // $email = trim(strtolower($email));
            $res = $this->table->find('email', $email);
            if (count($res) > 0 && !$header) {
                $errors[] = 'The email address or username may already be in use.';
            }
            if (!count($res) && $header) {
                $errors[] = 'Cannot validate email address for re-setting. Please try again.';
            }
        }
        return $errors;
    }

    private function getPeer($p, $b = 64)
    {
        $test = ($p % $b) === $p; // $b higher than $p returns $p
        if ($test) {
            $b /= 2;
            return $this->getPeer($p, $b);
        } else {
            return $b;
        }
    }

    private function listByAccess($editor)
    {
        $list = $this->table->findAll('permissions');
        $peer = $this->getPeer($editor->getPermission());
        $id = $editor->id;
        //editor cannot edit their peers
        //eg 1111 AND 1001 ARE PEERS
        $list = array_filter($list, function ($a) use ($peer, $id) {
            $subject = $a->id === $id;
            if ($subject) return $subject;
            $tmp = $this->getPeer($a->permissions);
            return $tmp < $peer;
        });
        return $list;
    }

    private function reRegister($user)
    {
        if ($user) {
            $this->table->delete('id', $user['id']);
            unset($user['id']);
            $user['password'] = $this->prepPassword('qwerty');
            $this->persist($user);
            reLocate(USER_RESET_PWD, '../../');
        }
    }

    private function checkEmail($old, $new, $id)
    {
        $errors = [];
        $values = $this->fetch('TABLE', 'email', $old);
        if (!empty($values)) {
            $errors = $this->validateEmail($new);
        } else {
            if (empty($new) || empty($old)) {
                $errors[] = 'Fields cannot be blank';
            }
            if ($old === $new) {
                $errors[] = 'Fields cannot match';
            }
            if (empty($errors)) {
                $errors[] = 'Existing email validation failed';
            }
        }
        if (empty($errors)) {
            $values['email'] = $new;
            $this->persist($values);
            reLocate(USER_RESET_EMAIL);
        }
        return $this->changeemail($id, $errors);
    }

    private function prepPassword($pwd, $hash = '')
    {
        if (!empty($hash)) {
            return password_verify($pwd, $hash);
        }
        return password_hash($pwd, PASSWORD_DEFAULT);
    }

    private function checkPassword($id, $old, $new)
    {
        $errors = [];
        $values = [];

        if (empty($new) || empty($old)) {
            $errors[] = 'Passwords cannot be blank';
        }
        if ($old === $new) {
            $errors[] = 'Fields cannot match';
        }

        $subject = $this->fetch('table', 'id', $id);
        /*
        $values will contain an instance of an Entity class as a (private) member
        so we need to filter $values of extraneous properties before saving as there will be an invalid parameter count
        a quick and dirty fix: $values = array_filter($values, 'is_string');
        but we'll use  "get_object_vars" which return ONLY public members as an array that the signature of save requires
        https://stackoverflow.com/questions/7039045/removing-private-properties-of-object
        */
        if (!empty($subject)) {
            $values = get_object_vars($subject); //public props only
        } else {
            $errors[] = 'User cannot be verified';
        }

        if (empty($errors) && $this->prepPassword($old, $values['password'])) {
            $values['password'] = $this->prepPassword($new);
            $this->persist($values);
            reLocate(USER_RESET_PWD . 'reset');
        }
        return $this->changepassword($id, $errors);
    }

    public function __construct(private DatabaseTable $table, private string $userid = '0', private mixed $permit = 0)
    {
    }

    public function access($msg = '')
    {
        //after failing we don't want any ajax so this will prevent loading js
        return $this->admin($msg);
    }

    public function admin($msg = '')
    {
        $cookies = array_filter(['page', 'altmode', 'error'], partial('getProp', $_COOKIE));
        array_map(doSetCookie(false), $cookies);
        $editor = $this->fetch('table', 'id', $this->userid);
        $admin = $editor->hasPermission(\PoloAfrica\Entity\User::ACCOUNT_EDITOR);
        $name = $editor->name;
        $username = "user you are logged in as $name";

        if (!isset($_SESSION['filestore'])) {
            if ($editor->checkPermission(127)) {
                $_SESSION['filestore'] = 'filestore';
            } else {
                unset($_SESSION['filestore']);
            }
        }

        if ($msg === 'success') {
            return [
                'template' => 'actions.html.php',
                'title' => 'Log In Successful',
                'variables' => [
                    'userid' => $user->id ?? '',
                    'admin' => $admin,
                    'username' => $username,
                    'user' => $editor,
                    'msg' => $msg
                ]
            ];
        }

        if (!empty($editor)) {
            return [
                'template' => 'actions.html.php',
                'variables' => [
                    'permissions' => $editor->hasPermission(\PoloAfrica\Entity\User::ACCOUNT_EDITOR),
                    'msg' => $msg,
                    'username' => $editor->name ?? ''
                ]
            ];
        }
    }

    public function register($userid = '', $err = [], $msg = '')
    {
        /*
        Can't protect route to register as prospective clients need to be able to register.
        ONLY a user may EDIT their own details, ADMIN can delete users*/
        $subject = null;
        $owner = false;
        $permissions = 0;
        if (!empty($userid)) {
            $subject = $this->table->find('id', $userid)[0] ?? null;
            $editor = $this->table->find('id', $this->userid)[0] ?? null;
            $pass = $editor && $subject;
            if (!$pass) {
                reLocate(BADMINTON, '../../');
            }
        }
        $action = empty($subject) ? USER_REG : USER_EDIT;
        $submit = empty($subject) ? 'Register' : 'Edit';
        if (isset($editor)) {
            $owner = $this->authorise($editor->checkPermission(\PoloAfrica\Entity\User::ADMIN), $editor->id, $subject->id);
            $permissions =  $editor->hasPermission(\PoloAfrica\Entity\User::ACCOUNT_EDITOR);
        }

        return [
            'template' => 'register.html.php',
            'title' => 'Register an account',
            'variables' => [
                'errors' => $err,
                'action' => $action,
                'route' => 'register',
                'submit' => $submit,
                'userid' => $userid ?? 0,
                'msg' => $msg,
                'permissions' => $permissions,
                'username' => $subject->name ?? '',
                'owner' => $owner,
                'exit' => BADMINTON
            ]
        ];
    }

    public function success($arg = '')
    {
        return [
            'template' => 'registersuccess.html.php',
            'title' => 'Registration Successful'
        ];
    }
    public function resetpassword($pwd = '')
    {
        $para = 'Password has been reset, please login with your new password.';
        if (empty($pwd)) {
            $para = 'Password is now <strong>qwerty</strong> which we we would encourage you to change on login.';
        }
        return [
            'template' => 'reset.html.php',
            'title' => 'Password Reset Successful',
            'variables' => [
                'title' => 'Password Reset Successful',
                'para' => $para
            ]
        ];
    }

    public function resetemail()
    {
        return [
            'template' => 'reset.html.php',
            'title' => 'Email Reset Successful',
            'variables' => [
                'title' => 'Email Reset Successful',
                'para' => 'Email has been reset, please login with your new email address.'
            ]
        ];
    }

    public function delete()
    {
        if (!empty($_POST['cancel'])) {
            reLocate(BADMINTON, '../../');
        } else {
            $userid = $_POST['pk'] ?? 0;
            $owner = $this->fetch('table', 'id', $this->userid);
            $candidate = $this->fetch('table', 'id', $userid);
            $admin = $this->getPeer(\PoloAfrica\Entity\User::ADMIN);
            if ($owner && $candidate) {
                $current = $this->getPeer($candidate->permissions);
                if ($this->authorise($admin > $current, $owner->id, $userid)) {
                    $this->table->delete('id', $userid);
                    reLocate(BADMINTON, '../../');
                } else {
                    //SEE exclaim in FUNCTIONS
                    $feedback = '/!you do not have the required permissions to delete that user.';
                    reLocate(BADMINTON . $feedback, '../../');
                }
            }
        }
    }

    public function confirm($id = 0)
    {
        $user = $this->fetch('table', 'id', $id);
        if (!$user) {
            reLocate(BADMINTON . '/!user not found', '../../');
        }
        return [
            'template' => 'delete.html.php',
            'variables' => [
                'identity' => 'delete',
                'exit' => USER_LIST,
                'action' => USER_D1,
                'id' => $id,
                'file' => $user,
                'confirm' => '',
                'perform' => 'delete',
                'submit' => 'submit'
            ]
        ];
    }
    public function list()
    {
        $list = [];
        if (isset($_SESSION['username'])) {
            $editor = $this->table->find('email', $_SESSION['username'])[0] ?? null;
        }
        if (isset($editor) && $editor->hasPermission(\PoloAfrica\Entity\User::ACCOUNT_EDITOR)) {
            $list = $this->listByAccess($editor);
        }

        if (empty($list)) {
            return [
                'template' => 'accessdenied.html.php',
                'variables' => [
                    'str' => 'user',
                    'accesslevel' => $this->getAccess(0),
                    'submitted' => false,
                    'id' => $editor->id ?? null
                ]
            ];
        }

        return [
            'template' => 'userlist.html.php',
            'title' => 'Admin',
            'variables' => [
                'errors' => '',
                'action' => USER_D2,
                'exit' => BADMINTON,
                'files' => $list,
                'editor' => $editor,
                'target' => $editor->id ?? null
            ]
        ];
    }

    public function edit($id = 0)
    {
        $subject = $this->fetch('table', 'id', $id);
        if (empty($subject)) {
            $this->exit($subject);
        }
        return $this->register($subject->id, [], [], USER_EDIT);
    }


    public function permissions($id = 0)
    {
        $subject = $this->table->find('id', $id);
        $editor = $this->fetch('table', 'id', $this->userid);
        if (empty($subject)) {
            $this->exit($subject);
        }
        $subject = $subject[0];
        $reflected = new \ReflectionClass('\PoloAfrica\Entity\User');
        $constants = $reflected->getConstants();
        $pretty = [];
        $tmp = '';
        foreach ($constants as $k => $v) {
            if ($editor->permissions < $v) {
                break;
            }
            $tmp = beautify($k);
            $pretty[$tmp] = $v;
        }
        return [
            'template' => 'permissions.html.php',
            'title' => 'Edit permissions',
            'variables' => [
                'user' => $subject,
                'permissions' => $pretty,
                'action' => USER_PERMIT . $subject->id,
                'exit' => '.'
            ]
        ];
    }
    public function changepassword($id = 0, $errors = [])
    {
        $subject = $this->fetch('table', 'id', $id);
        if (empty($subject)) {
            //review
            if (!empty($_SESSION['username'])) {
                //  $subject = $this->fetch('table', 'email', $_SESSION['username']);
            }
            $this->exit($subject);
        }

        return [
            'template' => 'change.html.php',
            'title' => 'Change Password',
            'variables' => [
                'id' => $id,
                'action' => USER_EDIT,
                'user' => $subject,
                'errors' => $errors,
                'msg' => '',
                'old' => 'oldpassword',
                'neu' => 'password',
                'type' => 'text',
                'title' => 'Change Password',
                'email' => '',
                'submit' => 'submit',
                'exit' => BBC
            ]
        ];
    }


    public function changeemail($id = 0, $errors = [])
    {
        $subject = $this->fetch('table', 'id', $id);

        if (empty($subject)) {
            $this->exit($subject);
        }

        return [
            'template' => 'change.html.php',
            'title' => 'Change Email',
            'variables' => [
                'id' => $id,
                'action' => USER_EDIT,
                'user' => $subject,
                'errors' => $errors,
                'msg' => '',
                'email' => $subject->email,
                'old' => 'oldemail',
                'neu' => 'email',
                'type' => 'email',
                'title' => 'Change Email',
                'submit' => 'submit'
            ]
        ];
    }

    public function permit($id = 0)
    {
        return $this->edit($id);
    }

    public function contact($id = 0, $errors = [])
    {
        return [
            'template' => 'password_recovery.html.php',
            'title' => 'Password Recovery',
            'variables' => [
                'user' => '',
                'permissions' => '',
                'id' => $id ?? '',
                'errors' => $errors,
                'msg' => '',
                'klas' => 'user edit'
            ]
        ];
    }

    public function message($str = '', $i = 0)
    {
        $str = exclaim($str);

        if ($str) {
            return [
                'template' => 'accessdenied.html.php',
                'variables' => [
                    'str' => $str,
                    'accesslevel' => $this->getAccess($i),
                    'submitted' => false
                ]
            ];
        } else {
            reLocate(REG);
        }
    }

    public function editSubmit($id = 0)
    {
        $msg = '';
        $values = $_POST['user'] ?? '';
        $errors = [];
        if (!empty($values)) {

            if (isset($values['oldpassword'])) {
                return $this->checkPassword($id, $values['oldpassword'], $values['password']);
            }
            if (isset($values['oldemail'])) {
                return $this->checkEmail($values['oldemail'], $values['email'], $id);
            }
            if (isset($values['name'])) {
                if (empty($values['name'])) {
                    $errors[] = 'Name cannot be blank';
                }
            }
            if (!empty($errors)) {
                return $this->register($id, $errors, $msg);
            } else {
                $values['id'] = $id;
                $subject = $this->persist($values);
                $this->exit($subject, '/gebruiker');
            }
        } else {
            retour();
        }
    }

    public function registerSubmit($id = 0, $header = '')
    {
        $values = $_POST['user'] ?? '';
        $msg = '';
        $errors = [];
        if (!empty($values)) {
            if (isset($values['email'])) {
                $email = $values['email'];
                $msg = 'Your account could not be created, please check the following:';
                $msg = $header ? 'Unable to login, please check the following:' : $msg;
                $errors = $this->validateEmail($email, $header);
                if (empty($values['password'])) {
                    $errors[] = 'Password cannot be blank';
                }
            }
            if (empty($errors)) {
                $errors = $this->fetch('table', 'name', $values['name']);
                if ($errors) {
                    $errors = ['That email or username may already in use, please try again'];
                    return $this->register($values['id'] ?? '', $errors, $msg);
                }
                $values['password'] = $this->prepPassword($values['password']);
                $values['permissions'] = 1;
                $this->persist($values);
                reLocate(USER_OK, '../../');
            } else {
                return $this->register($values['id'] ?? '', $errors, $msg);
            }
        } else {
            retour();
        }
    }

    public function permissionsSubmit($id = null)
    {
        if (isset($_POST['id'])) {
            $values = [
                'id' => $_POST['id'],
                'permissions' => array_sum($_POST['permissions'] ?? [])
            ];
            return $this->exit($this->persist($values), '/gebruiker');
        } else {
            retour();
        }
    }

    public function contactSubmit()
    {
        $id = $_POST['pk'] ?? '';
        if (isset($id)) {
            $email = $_POST['user']['email'];
            $errors = $this->validateEmail($email, true);
            if (empty($errors)) {
                $this->reRegister($this->fetch('TABLE', 'email', $email));
            }
            return $this->contact($id, $errors);
        } else {
            retour();
        }
    }
}
