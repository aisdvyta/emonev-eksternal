<?php

namespace App\Livewire\Master;

use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\MasterSatker;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;

    protected $listeners = ['refreshTable' => '$refresh'];

    public $search = '';
    public $roleFilter = [];
    public $kodeSatkerFilter = [];
    public $namaSatkerFilter = [];
    public $perPage = 10;
    public $hideNip = false;

    // Reset pagination when search or filters change
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function setFilterRoles(array $roles)
    {
        $this->roleFilter = $roles;
        $this->resetPage();
    }

    public function setFilterKodes(array $kodes)
    {
        $this->kodeSatkerFilter = $kodes;
        $this->resetPage();
    }

    public function setFilterKabkots(array $kodes)
    {
        $this->namaSatkerFilter = $kodes;
        $this->resetPage();
    }

    public function render()
    {
        $query = User::with(['roles', 'masterSatker'])
            ->orderBy(
                \Illuminate\Support\Facades\DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->whereColumn('model_has_roles.model_id', 'users.id_user')
                    ->where('model_has_roles.model_type', User::class)
                    ->selectRaw("
                        CASE 
                            WHEN roles.name = 'admin' THEN 1
                            WHEN roles.name = 'super_evaluator' THEN 2
                            WHEN roles.name = 'evaluator' THEN 3
                            WHEN roles.name = 'penilai_eksternal' THEN 4
                            WHEN roles.name = 'kabag' THEN 5
                            WHEN roles.name = 'verifikator' THEN 6
                            WHEN roles.name = 'operator' THEN 7
                            ELSE 8
                        END
                    ")
                    ->limit(1)
            )
            ->orderBy('users.id_user', 'asc');

        if (!empty(trim($this->search))) {
            $searchWildcard = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchWildcard) {
                $q->where('nama_user', 'like', $searchWildcard)
                  ->orWhere('email', 'like', $searchWildcard)
                  ->orWhere('nip_panjang', 'like', $searchWildcard)
                  ->orWhere('nip_pendek', 'like', $searchWildcard);
            });
        }

        if (!empty($this->roleFilter)) {
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', $this->roleFilter);
            });
        }

        if (!empty($this->kodeSatkerFilter)) {
            $query->whereIn('kode_satker', $this->kodeSatkerFilter);
        }

        if (!empty($this->namaSatkerFilter)) {
            $query->whereIn('kode_satker', $this->namaSatkerFilter);
        }

        $users = $query->paginate($this->perPage);
        $roles = \Illuminate\Support\Facades\Cache::remember('roles_all', 3600, function() {
            return Role::all();
        });
        $satkers = \Illuminate\Support\Facades\Cache::remember('satkers_ordered', 3600, function() {
            return MasterSatker::orderBy('kode_satker')->get();
        });

        return view('livewire.master.user-table', [
            'users' => $users,
            'roles' => $roles,
            'satkers' => $satkers,
        ]);
    }
}
