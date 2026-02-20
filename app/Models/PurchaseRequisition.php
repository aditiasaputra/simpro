<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequisition extends Model
{
    protected $guarded = [];

    public function details()
    {
        return $this->hasMany(PurchaseRequisitionDetail::class);
    }
}
