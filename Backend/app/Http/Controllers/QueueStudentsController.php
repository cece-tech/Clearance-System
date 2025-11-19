<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clearance;
use App\Models\ClearanceApprover;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ClearanceController extends Controller
{
    /**
     * Get all clearance requests with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,approved,rejected,completed',
            'request_type' => 'nullable|string',
            'year_level' => 'nullable|string',
            'section' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Clearance::with([
            'student',
            'approvers.approver',
            'approvers' => function($q) {
                $q->orderBy('order');
            }
        ]);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('student_id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Request type filter
        if ($request->filled('request_type')) {
            $query->where('request_type', $request->request_type);
        }

        // Year level filter
        if ($request->filled('year_level')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('year_level', $request->year_level);
            });
        }

        // Section filter
        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        $clearances = $query->latest()
            ->paginate($request->per_page ?? 15);

        // Transform data for frontend
        $data = $clearances->map(function($clearance) {
            return [
                'id' => $clearance->id,
                'student_id' => $clearance->student->student_id ?? $clearance->student_id,
                'student_name' => $clearance->student->name ?? 'N/A',
                'section' => $clearance->section,
                'year_level' => $clearance->student->year_level ?? 'N/A',
                'request_type' => $clearance->request_type,
                'status' => $clearance->status,
                'created_at' => $clearance->created_at->format('Y-m-d H:i:s'),
                'approvers' => $clearance->approvers->map(function($approver) {
                    return [
                        'name' => $approver->approver_name,
                        'role' => $approver->approver_role,
                        'status' => $approver->status,
                        'remarks' => $approver->remarks,
                        'approved_at' => $approver->approved_at?->format('Y-m-d H:i:s'),
                    ];
                }),
                'action_type' => $this->determineActionType($clearance),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $clearances->currentPage(),
                'last_page' => $clearances->lastPage(),
                'per_page' => $clearances->perPage(),
                'total' => $clearances->total(),
            ],
        ]);
    }

    /**
     * Get single clearance request details
     */
    public function show($id): JsonResponse
    {
        $clearance = Clearance::with([
            'student',
            'approvers.approver',
            'documents'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $clearance->id,
                'student_id' => $clearance->student->student_id,
                'student_name' => $clearance->student->name,
                'email' => $clearance->student->email,
                'section' => $clearance->section,
                'year_level' => $clearance->student->year_level,
                'request_type' => $clearance->request_type,
                'status' => $clearance->status,
                'notes' => $clearance->notes,
                'created_at' => $clearance->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $clearance->updated_at->format('Y-m-d H:i:s'),
                'approvers' => $clearance->approvers->map(function($approver) {
                    return [
                        'id' => $approver->id,
                        'name' => $approver->approver_name,
                        'role' => $approver->approver_role,
                        'status' => $approver->status,
                        'remarks' => $approver->remarks,
                        'approved_at' => $approver->approved_at?->format('Y-m-d H:i:s'),
                    ];
                }),
                'documents' => $clearance->documents,
            ],
        ]);
    }

    /**
     * Create new clearance request
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'section' => 'required|string|max:50',
            'request_type' => 'required|in:enrollment,course_change,financial_aid,document_submission,graduation,transfer,leave_of_absence',
            'notes' => 'nullable|string',
            'approvers' => 'required|array|min:1',
            'approvers.*.role' => 'required|string',
            'approvers.*.name' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $clearance = Clearance::create([
                'student_id' => $request->student_id,
                'section' => $request->section,
                'request_type' => $request->request_type,
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            // Create approvers
            foreach ($request->approvers as $index => $approverData) {
                ClearanceApprover::create([
                    'clearance_id' => $clearance->id,
                    'approver_role' => $approverData['role'],
                    'approver_name' => $approverData['name'],
                    'order' => $index + 1,
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Clearance request created successfully',
                'data' => $clearance->load('approvers'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create clearance request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update clearance request
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'section' => 'sometimes|string|max:50',
            'request_type' => 'sometimes|in:enrollment,course_change,financial_aid,document_submission,graduation,transfer,leave_of_absence',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,in_progress,approved,rejected,completed',
        ]);

        $clearance = Clearance::findOrFail($id);
        $clearance->update($request->only(['section', 'request_type', 'notes', 'status']));

        return response()->json([
            'success' => true,
            'message' => 'Clearance request updated successfully',
            'data' => $clearance,
        ]);
    }

    /**
     * Bulk actions for clearances
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:clearances,id',
            'action' => 'required|in:approve,reject,delete,mark_complete,mark_pending',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $clearances = Clearance::whereIn('id', $request->ids)->get();

            foreach ($clearances as $clearance) {
                switch ($request->action) {
                    case 'approve':
                        $clearance->update(['status' => 'approved']);
                        break;
                    case 'reject':
                        $clearance->update([
                            'status' => 'rejected',
                            'notes' => $request->remarks,
                        ]);
                        break;
                    case 'delete':
                        $clearance->delete();
                        break;
                    case 'mark_complete':
                        $clearance->update(['status' => 'completed']);
                        break;
                    case 'mark_pending':
                        $clearance->update(['status' => 'pending']);
                        break;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk action '{$request->action}' completed successfully",
                'affected_count' => count($request->ids),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update approver status
     */
    public function updateApprover(Request $request, $clearanceId, $approverId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,pending',
            'remarks' => 'nullable|string',
        ]);

        $approver = ClearanceApprover::where('clearance_id', $clearanceId)
            ->where('id', $approverId)
            ->firstOrFail();

        $approver->update([
            'status' => $request->status,
            'remarks' => $request->remarks,
            'approved_at' => $request->status === 'approved' ? now() : null,
        ]);

        // Update clearance status based on approvers
        $this->updateClearanceStatus($clearanceId);

        return response()->json([
            'success' => true,
            'message' => 'Approver status updated successfully',
            'data' => $approver,
        ]);
    }

    /**
     * Get available request types
     */
    public function getRequestTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'enrollment' => 'Enrollment',
                'course_change' => 'Course Change',
                'financial_aid' => 'Financial Aid',
                'document_submission' => 'Document Submission',
                'graduation' => 'Graduation',
                'transfer' => 'Transfer',
                'leave_of_absence' => 'Leave of Absence',
            ],
        ]);
    }

    /**
     * Get available approver roles
     */
    public function getApproverRoles(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'organization_treasurer' => 'Organization Treasurer',
                'organization_adviser' => 'Organization Adviser',
                'librarian' => 'Librarian',
                'year_level_treasurer' => 'Year Level Treasurer',
                'program_head' => 'Program Head',
                'dean' => 'Dean',
                'registrar' => 'Registrar',
                'vpsd' => 'VPSD (Vice President for Student Development)',
                'finance_office' => 'Finance Office',
                'scholarship_office' => 'Scholarship Office',
            ],
        ]);
    }

    /**
     * Determine action type based on clearance status
     */
    private function determineActionType($clearance): string
    {
        if ($clearance->status === 'completed') {
            return 'view';
        }

        if ($clearance->status === 'rejected') {
            $allRejected = $clearance->approvers->every(function($approver) {
                return $approver->status === 'rejected';
            });
            return $allRejected ? 'view' : 'review';
        }

        if (in_array($clearance->status, ['pending', 'in_progress'])) {
            return 'review';
        }

        if ($clearance->status === 'approved') {
            return 'clearance';
        }

        return 'review';
    }

    /**
     * Update clearance status based on approvers
     */
    private function updateClearanceStatus($clearanceId): void
    {
        $clearance = Clearance::with('approvers')->findOrFail($clearanceId);
        
        $allApproved = $clearance->approvers->every(function($approver) {
            return $approver->status === 'approved';
        });

        $anyRejected = $clearance->approvers->contains(function($approver) {
            return $approver->status === 'rejected';
        });

        if ($allApproved) {
            $clearance->update(['status' => 'completed']);
        } elseif ($anyRejected) {
            $clearance->update(['status' => 'rejected']);
        } else {
            $clearance->update(['status' => 'in_progress']);
        }
    }
}