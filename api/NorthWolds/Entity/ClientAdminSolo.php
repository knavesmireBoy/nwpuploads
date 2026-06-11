<?php

namespace NorthWolds\Entity;
//if this is the only admin role we cannot demote or delete
class ClientAdminSolo extends ClientAdmin
{

    protected function validateRole($role)
    {
        $details = $this->getDetails();
        if (empty($details)) {
            return $role;
        }
        $ids = $this->getUserIds();
        if (in_array($role, $this->roles)) {
            $roles = $this->getAllRoles($ids);
            $roles = $this->getAdminRoles($roles);
            if (!empty($roles)) {
                $i = array_search($role, $this->roles);
                $j = array_search($this->roleid, $this->roles);
                if ($i < $j) { //demotion
                    return $this->self ? 'lastadminrole' : '_lastadminrole';
                }
            }
        }
        return $role;
    }

    public function delete($id, $details)
    {
        $msg = '';
        $ids = $this->getUserIds();
        $roles = $this->getAllRoles($ids);
        $adminroles = $this->getAdminRoles($roles);
        $ids = array_column($adminroles, 'userid');
        $key = in_array($this->id, $ids) ? 'lasteditor' : '';
        $msg = $key;
        return $msg;
    }
}
