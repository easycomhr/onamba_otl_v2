<?php

namespace App\Services;

class LegacyLeaveTypeMapper
{
    /**
     * Map leave types to legacy tblleavetypes.leavetypeid
     *
     * annual   → 1  Nghỉ phép năm       (paidrate 100%)
     * sick     → 5  Nghỉ đột xuất       (paidrate 100%, sudden paid leave)
     * personal → 5  Nghỉ đột xuất       (legacy has no distinct personal type)
     * unpaid   → 4  Nghỉ không lương    (paidrate 0%)
     */
    public const MAPPING = [
        'annual'   => 1,
        'sick'     => 5,
        'personal' => 5,
        'unpaid'   => 4,
    ];

    public static function toLegacyId(string $key): int
    {
        return self::MAPPING[$key] ?? 0;
    }

    /**
     * Reverse lookup. Note: legacy ID 5 maps to both 'sick' and 'personal';
     * this returns 'sick' by convention (first match).
     */
    public static function fromLegacyId(int $id): string
    {
        $key = array_search($id, self::MAPPING, true);

        return $key === false ? '' : $key;
    }
}
