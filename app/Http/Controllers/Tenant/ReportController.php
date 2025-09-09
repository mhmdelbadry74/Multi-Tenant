<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\ReportResource;
use Illuminate\Http\Request;
use App\Models\Tenant\Deal;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Activity;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get deals summary report
     */
    public function deals(Request $request)
    {
        $totalDeals = Deal::count();
        $openDeals = Deal::where('status', 'open')->count();
        $wonDeals = Deal::where('status', 'won')->count();
        $lostDeals = Deal::where('status', 'lost')->count();
        
        $totalAmount = Deal::sum('amount');
        $wonAmount = Deal::where('status', 'won')->sum('amount');
        $openAmount = Deal::where('status', 'open')->sum('amount');
        $lostAmount = Deal::where('status', 'lost')->sum('amount');

        $dealsByStatus = Deal::select('status', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
            ->groupBy('status')
            ->get();

        $dealsByMonth = Deal::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('count(*) as count'),
                DB::raw('sum(amount) as total_amount')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'data' => new ReportResource([
                'summary' => [
                    'total_deals' => $totalDeals,
                    'open_deals' => $openDeals,
                    'won_deals' => $wonDeals,
                    'lost_deals' => $lostDeals,
                    'total_amount' => $totalAmount,
                    'won_amount' => $wonAmount,
                    'open_amount' => $openAmount,
                    'lost_amount' => $lostAmount,
                ],
                'by_status' => $dealsByStatus,
                'by_month' => $dealsByMonth,
            ]),
            'message' => 'Deals report generated successfully'
        ]);
    }

    /**
     * Get contacts summary report
     */
    public function contacts(Request $request)
    {
        $totalContacts = Contact::count();
        $contactsWithDeals = Contact::has('deals')->count();
        $contactsWithoutDeals = Contact::doesntHave('deals')->count();

        $contactsByCompany = Contact::select('company', DB::raw('count(*) as count'))
            ->whereNotNull('company')
            ->groupBy('company')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $recentContacts = Contact::with('creator')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => new ReportResource([
                'summary' => [
                    'total_contacts' => $totalContacts,
                    'contacts_with_deals' => $contactsWithDeals,
                    'contacts_without_deals' => $contactsWithoutDeals,
                ],
                'by_company' => $contactsByCompany,
                'recent_contacts' => $recentContacts,
            ]),
            'message' => 'Contacts report generated successfully'
        ]);
    }

    /**
     * Get activities summary report
     */
    public function activities(Request $request)
    {
        $totalActivities = Activity::count();
        
        $activitiesByType = Activity::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        $activitiesByMonth = Activity::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $recentActivities = Activity::with(['user', 'contact', 'deal'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => new ReportResource([
                'summary' => [
                    'total_activities' => $totalActivities,
                ],
                'by_type' => $activitiesByType,
                'by_month' => $activitiesByMonth,
                'recent_activities' => $recentActivities,
            ]),
            'message' => 'Activities report generated successfully'
        ]);
    }
}
