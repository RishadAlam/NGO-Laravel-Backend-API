<?php

namespace App\Http\Controllers\Audit;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Ramsey\Uuid\Type\Integer;
use PhpParser\Node\Stmt\Static_;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Audit\AuditReportMeta;
use App\Models\Audit\AuditReportPage;
use App\Models\Audit\AuditReportMetaActionHistory;
use App\Http\Requests\Audit\AuditReportMetaStoreRequest;
use App\Http\Requests\Audit\AuditReportMetaUpdateRequest;

class AuditReportMetaController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:audit_report_meta_list_view')->only('index');
        $this->middleware('can:audit_report_meta_create')->only('store');
        $this->middleware('can:audit_report_meta_update')->only('update');
        $this->middleware('can:audit_report_meta_soft_delete')->only('destroy');
        $this->middleware('can:audit_report_meta_permanently_delete')->only('permanent_delete');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $meta_keys = AuditReportPage::with(['AuditReportMeta', 'AuditReportMeta.author:id,name', 'AuditReportMeta.AuditReportMetaActionHistory'])
            ->get(['id', 'name', 'is_default']);

        return create_response(null, $meta_keys);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AuditReportMetaStoreRequest $request)
    {
        $data = (object) $request->validated();
        AuditReportMeta::create(Self::setFieldMap($data, true));

        return create_response(__('customValidations.audit.meta.successful'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AuditReportMetaUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $meta       = AuditReportMeta::with('AuditReportPage:id,name,is_default')->find($id);
        $histData   = [];

        $meta->meta_key             !== $data->meta_key ? $histData['meta_key'] = "<p class='text-danger'>{$meta->meta_key}</p><p class='text-success'>{$data->meta_key}</p>" : '';
        $meta->meta_value           !== $data->meta_value ? $histData['meta_value'] = "<p class='text-danger'>{$meta->meta_value}</p><p class='text-success'>{$data->meta_value}</p>" : '';
        $meta->audit_report_page_id !== $data->audit_report_page_id ? $histData['page'] = "<p class='text-danger'>{$meta->AuditReportPage->name}</p><p class='text-success'>{$request->page['name']}</p>" : '';
        $meta->column_no            !== $data->column_no ? $histData['column'] = "<p class='text-danger'>{$meta->column_no}</p><p class='text-success'>{$data->column_no}</p>" : '';

        DB::transaction(function () use ($id, $data, $meta, $histData) {
            $meta->update(Self::setFieldMap($data));
            AuditReportMetaActionHistory::create(Helper::setActionHistory('audit_report_meta_id', $id, 'update', $histData));
        });

        return create_response(__('customValidations.audit.meta.update'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            AuditReportMeta::find($id)->delete();
            AuditReportMetaActionHistory::create(Helper::setActionHistory('audit_report_meta_id', $id, 'delete', []));
        });

        return create_response(__('customValidations.audit.meta.delete'));
    }

    /**
     * Field Map
     */
    private static function setFieldMap(object $data, bool $isNew = false)
    {
        $map = [
            'meta_key'              => $data->meta_key,
            'meta_value'            => $data->meta_value,
            'column_no'             => $data->column_no,
            'audit_report_page_id'  => $data->audit_report_page_id,
        ];

        if ($isNew) {
            $map['creator_id'] = auth()->id();
        }

        return $map;
    }
}
