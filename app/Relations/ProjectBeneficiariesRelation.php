<?php

namespace App\Relations;

use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A project's beneficiaries, now a plain one-to-many.
 *
 * The app never calls attach/detach; those methods exist only so older tests,
 * seeders and one-off setup scripts keep their existing shape while the UI moves
 * to create/edit/archive. They update `beneficiaries.extension_project_id`;
 * there is no pivot table any more.
 */
class ProjectBeneficiariesRelation extends HasMany
{
    public function attach($ids, array $attributes = [], $touch = true): void
    {
        $this->assign($ids);
    }

    public function syncWithoutDetaching($ids): void
    {
        $this->assign($ids);
    }

    public function sync($ids): void
    {
        $ids = $this->normaliseIds($ids);

        $this->getQuery()
            ->whereNotIn('beneficiaries.id', $ids)
            ->update(['extension_project_id' => null]);

        $this->assign($ids);
    }

    public function detach($ids = null): void
    {
        $query = $this->getQuery();

        if ($ids !== null) {
            $query->whereIn('beneficiaries.id', $this->normaliseIds($ids));
        }

        $query->update(['extension_project_id' => null]);
    }

    /** @param  mixed  $ids */
    private function assign($ids): void
    {
        $ids = $this->normaliseIds($ids);

        if ($ids === []) {
            return;
        }

        Beneficiary::query()
            ->whereIn('id', $ids)
            ->update(['extension_project_id' => $this->getParentKey()]);
    }

    /** @return array<int, int> */
    private function normaliseIds(mixed $ids): array
    {
        if ($ids instanceof Collection) {
            $ids = $ids->all();
        } elseif ($ids instanceof Model) {
            $ids = [$ids->getKey()];
        } elseif (! is_array($ids)) {
            $ids = [$ids];
        }

        return array_values(array_filter(array_map(
            fn ($id) => $id instanceof Model ? $id->getKey() : (int) $id,
            $ids
        )));
    }
}
