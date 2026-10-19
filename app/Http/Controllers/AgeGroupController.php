<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\PracticeSession;
use App\Support\AgeGroup;

/** Read-only overview: groups are automatic (from date of birth), nothing to create or edit. */
class AgeGroupController extends Controller
{
    public function index()
    {
        $slots  = array_keys(PracticeSession::slots());
        $both   = implode(',', $slots);
        $groups = [];

        foreach (AgeGroup::all() as $label) {
            $groups[$label] = ['label' => $label, 'range' => AgeGroup::rangeText($label), 'total' => 0, 'both' => 0, 'only' => array_fill_keys($slots, 0)];
        }

        // small table: one query, grouped in PHP
        foreach (Player::active()->get(['dob', 'training_days']) as $p) {
            $g = $p->age_group;
            if (!isset($groups[$g])) {
                continue;
            }
            $groups[$g]['total']++;
            if ($p->training_days === $both) {
                $groups[$g]['both']++;
            } elseif (isset($groups[$g]['only'][$p->training_days])) {
                $groups[$g]['only'][$p->training_days]++;
            }
        }

        return view('groups.index', [
            'groups' => $groups,
            'slots'  => PracticeSession::slots(),
        ]);
    }
}
