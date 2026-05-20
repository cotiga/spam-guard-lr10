<?php

namespace Cotiga\SpamGuard\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorIgnored extends Model
{
    protected $fillable = ['pattern'];
}
