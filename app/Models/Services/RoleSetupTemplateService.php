<?php

namespace App\Models\Services;

use App\Http\Resources\RoleSetupTemplateResource;
use App\Models\Enums\PageTypeEnum;
use App\Models\Enums\PermissionTypeEnum;
use App\Models\Enums\RoleTypeEnum;
use App\Models\RoleSetupTemplate;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class RoleSetupTemplateService extends ModelService
{
    public function __construct(RoleSetupTemplate $roleTemplates)
    {
        $this->roleTemplate = $roleTemplates;
        $this->model = $roleTemplates;
    }

    public static function create(RoleTypeEnum $role, string $setup_name, array $setup)
    {

        $ifSetupNameExist = RoleSetupTemplate::where('setup_name', $setup_name)->first();

        if($ifSetupNameExist === null){
            $setupValue = [];
            foreach($setup as $item){
                $setupValue[] = [
                    "page" => $item['page'],
                    "page_name" => PageTypeEnum::memberByValue($item['page'])->getLabel(),
                    "permission" => $item['permission']
                ];
            }
            $userrole = new RoleSetupTemplate();
            $userrole->role_id = $role;
            $userrole->setup_name = $setup_name;
            $userrole->setup = $setupValue;

            if($userrole->save()){
                return ResponseService::success('Succesfully created a new role.', new RoleSetupTemplateResource($userrole));
            }
        }

        return ResponseService::clientNotAllowed('Failed to create Role. Role Type name already exist.');

    }

    public function updateRoleSetupTemplate(RoleTypeEnum $role, string $setup_name, array $setup)
    {
        $setupValue = [];
        foreach($setup as $item){
            $setupValue[] = [
                "page" => $item['page'],
                "page_name" => PageTypeEnum::memberByValue($item['page'])->getLabel(),
                "permission" => $item['permission']
            ];
        }

        $this->roleTemplate->role_id = $role;
        $this->roleTemplate->setup_name = $setup_name;
        $this->roleTemplate->setup = $setupValue;
        $this->roleTemplate->save();

        return ResponseService::success('Succesfully updated a role.', new RoleSetupTemplateResource($this->roleTemplate));
    }

    public static function bulkDelete(array $ids)
    {
        foreach($ids as $id){
            if($userrole = RoleSetupTemplate::where('id', $id)){
                $userrole->delete();
            }
        }

        return ResponseService::success('User Role Templates has been deleted.', $ids);
    }

    public static function globalSearch(string $search)
    {
        return RoleSetupTemplate::where('setup_name', 'like', '%'.$search.'%')->get();
    }
}
