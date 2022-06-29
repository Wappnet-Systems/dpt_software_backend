<?php

namespace App\Helpers;

use App\Models\System\Organization;
use Hyn\Tenancy\Environment;

class TenantUtils
{
    /**
     * @param Organization|null $organization
     *
     * @return Organization|null
     */
    private function changeTenant(?Organization $organization): ?Organization
    {
        if ($organization && app(Environment::class)->tenant($organization->hostname->website) != null) {
            AppHelper::setDefaultDBConnection();

            return $organization;
        } else {
            return null;
        }
    }

    public function changeById(?string $id): ?Organization
    {
        if (empty($id)) return null;

        $organization = Organization::whereId($id)->first();
        
        return $this->changeTenant($organization);
    }

    public function changeByOrganization(Organization $organization): bool
    {
        return $this->changeTenant($organization) != null;
    }
}
