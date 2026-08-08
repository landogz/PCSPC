<?php

namespace App\Http\Resources\Leave;

use App\Models\LeaveBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeaveBalance */
class LeaveBalanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->relationLoaded('employee') ? $this->employee : null;
        $leaveType = $this->relationLoaded('leaveType') ? $this->leaveType : null;

        return [
            'id' => $this->uuid,
            'leave_year' => (int) $this->leave_year,
            'beginning' => number_format((float) $this->beginning, 2, '.', ''),
            'earned' => number_format((float) $this->earned, 2, '.', ''),
            'used' => number_format((float) $this->used, 2, '.', ''),
            'adjusted' => number_format((float) $this->adjusted, 2, '.', ''),
            'ending' => number_format($this->ending(), 2, '.', ''),
            'employee' => $employee ? [
                'id' => $employee->uuid,
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->fullName(),
                'department' => $employee->relationLoaded('department') && $employee->department
                    ? $employee->department->name
                    : null,
            ] : null,
            'leave_type' => $leaveType ? [
                'id' => $leaveType->uuid,
                'code' => $leaveType->code,
                'name' => $leaveType->name,
                'is_accruing' => (bool) $leaveType->is_accruing,
            ] : null,
        ];
    }
}
