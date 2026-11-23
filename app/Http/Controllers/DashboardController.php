<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Land each user on their role's home page: Search Job for a Gig
     * Worker, My Postings for a Contract Entity.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return $request->user()->isContractEntity()
            ? redirect()->route('employer.jobs.index')
            : redirect()->route('worker.jobs.index');
    }
}
