<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFamilyRequest;
use App\Http\Requests\UpdateFamilyRequest;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\History;
use App\Queries\FamilyListQuery;
use App\Support\BillUploadService;
use App\Support\FamilyHistoryLogger;
use App\Support\FamilyMemberReconciler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FamilyController extends Controller
{
    public function index(Request $request): View
    {
        $families = (new FamilyListQuery())->paginate(
            search: (string) $request->query('search', ''),
            sort: (string) $request->query('sort', ''),
            perPage: 20,
            page: (int) $request->query('page', 1),
        )->withQueryString();

        return view('families.index', [
            'families' => $families,
            'search' => (string) $request->query('search', ''),
            'sort' => (string) $request->query('sort', ''),
        ]);
    }

    public function create(): View
    {
        return view('families.create');
    }

    public function store(StoreFamilyRequest $request, BillUploadService $billUploads): RedirectResponse
    {
        $family = Family::create($request->safe()->toArray());

        $family->bill_of_master = $billUploads->store($request->file('bill_of_master') ?? [], null, null);
        $family->save();

        $rows = [];
        foreach ((array) $request->input('member_texts', []) as $text) {
            if (trim((string) $text) === '') {
                continue;
            }
            $rows[] = ['id' => null, 'text' => $text];
        }
        $diffs = FamilyMemberReconciler::reconcile($family, $rows);
        FamilyHistoryLogger::logDiffs($family->id, $diffs);

        return redirect()->route('families.index')->with('success', 'Thêm family thành công!');
    }

    public function edit(Family $family): View
    {
        return view('families.edit', ['family' => $family->load('members')]);
    }

    public function update(UpdateFamilyRequest $request, Family $family, BillUploadService $billUploads): RedirectResponse
    {
        $family->fill($request->safe()->only([
            'payment_at',
            'next_payment_at',
            'email',
            'number_phone',
            'number_bank',
            'name_bank',
            'user',
            'afiilicate_by',
            'note',
        ]));

        $family->bill_of_master = $billUploads->store($request->file('bill_of_master') ?? [], null, $family->bill_of_master);
        $family->bill_payment = $billUploads->store($request->file('bill_payment') ?? [], null, $family->bill_payment);
        $family->save();

        $ids = (array) $request->input('member_ids', []);
        $rows = [];
        foreach ((array) $request->input('member_texts', []) as $index => $text) {
            if (trim((string) $text) === '') {
                continue;
            }
            $rows[] = [
                'id' => isset($ids[$index]) && $ids[$index] !== '' ? (int) $ids[$index] : null,
                'text' => $text,
            ];
        }
        $diffs = FamilyMemberReconciler::reconcile($family, $rows);
        FamilyHistoryLogger::logDiffs($family->id, $diffs);

        return redirect()->back()->with('success', 'Cập nhật family thành công!');
    }

    public function destroy(Family $family): RedirectResponse
    {
        $family->delete();

        return redirect()->route('families.index')->with('success', 'Xóa family thành công!');
    }

    public function quickPay(Request $request, Family $family, BillUploadService $billUploads): RedirectResponse
    {
        $data = $request->validate([
            'months' => ['nullable', 'integer', 'min:0'],
            'bill_payment_paste' => ['nullable', 'string'],
        ]);

        $family->bill_payment = $billUploads->store(
            $request->file('bill_payment') ?? [],
            $data['bill_payment_paste'] ?? null,
            $family->bill_payment,
        );

        $family->payment_at = now()->toDateString();
        $family->next_payment_at = ($family->next_payment_at ?? now())->copy()->addMonths((int) ($data['months'] ?? 0));
        $family->save();

        return redirect()->route('families.index')->with('success', "Đã cập nhật thanh toán cho chủ farm: {$family->user}.");
    }

    public function checkMemberEmail(Request $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->query('email', '')));
        $excludeFamilyId = (int) $request->query('exclude_id', 0);

        if ($email === '') {
            return response()->json(['exists' => false, 'family' => null]);
        }

        $ownerMatch = Family::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->when($excludeFamilyId > 0, fn($q) => $q->where('id', '!=', $excludeFamilyId))
            ->first();

        if ($ownerMatch) {
            return response()->json(['exists' => true, 'family' => ['id' => $ownerMatch->id, 'user' => $ownerMatch->user, 'email' => $ownerMatch->email]]);
        }

        $memberMatch = FamilyMember::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->when($excludeFamilyId > 0, fn($q) => $q->where('family_id', '!=', $excludeFamilyId))
            ->with('family')
            ->first();

        if ($memberMatch && $memberMatch->family) {
            return response()->json(['exists' => true, 'family' => ['id' => $memberMatch->family->id, 'user' => $memberMatch->family->user, 'email' => $memberMatch->family->email]]);
        }

        return response()->json(['exists' => false, 'family' => null]);
    }

    public function history(Family $family): JsonResponse
    {
        return response()->json(
            History::where('family_id', $family->id)->orderByDesc('id')->get()
        );
    }

    public function historySearch(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        if ($search === '') {
            return response()->json([]);
        }

        $term = '%'.$search.'%';

        $items = History::query()
            ->join('families', 'families.id', '=', 'history_joining_family.family_id')
            ->where(function ($query) use ($term) {
                $query->where('history_joining_family.order_id', 'like', $term)
                    ->orWhere('history_joining_family.email', 'like', $term)
                    ->orWhere('history_joining_family.name_product', 'like', $term)
                    ->orWhere('families.user', 'like', $term);
            })
            ->orderByDesc('history_joining_family.id')
            ->limit(100)
            ->get(['history_joining_family.*', 'families.user as family_user']);

        return response()->json($items);
    }
}
