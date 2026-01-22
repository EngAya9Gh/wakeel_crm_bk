<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Appointments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Appointments\StoreAppointmentRequest;
use App\Http\Requests\Api\V1\Appointments\UpdateAppointmentRequest;
use App\Http\Requests\Api\V1\Appointments\ChangeAppointmentStatusRequest;
use App\Http\Resources\Api\V1\Appointments\AppointmentResource;
use App\Http\Resources\Api\V1\Appointments\AppointmentCollectionResource;
use App\Models\Appointment;
use App\Services\Appointments\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    use \App\Traits\ApiResponse;

    public function __construct(
        protected AppointmentService $appointmentService
    ) {}

    /**
     * GET /api/v1/appointments
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'status', 'type', 'client_id', 'user_id',
            'date_from', 'date_to', 'sort_by', 'sort_dir'
        ]);

        $appointments = $this->appointmentService->getAppointments(
            $filters,
            $request->input('per_page', 15)
        );

        return $this->paginatedResponse($appointments, 'تم جلب المواعيد بنجاح');
    }

    /**
     * POST /api/v1/appointments
     */
    public function store(StoreAppointmentRequest $request)
    {
        $appointment = $this->appointmentService->createAppointment(
            $request->validated(),
            $request->user()->id
        );

        return $this->createdResponse(new AppointmentResource($appointment), 'تم إنشاء الموعد بنجاح');
    }

    /**
     * GET /api/v1/appointments/{appointment}
     */
    public function show(Appointment $appointment)
    {
        return $this->successResponse(new AppointmentResource($appointment->load(['client', 'user'])));
    }

    /**
     * PUT /api/v1/appointments/{appointment}
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $updatedAppointment = $this->appointmentService->updateAppointment(
            $appointment,
            $request->validated()
        );

        return $this->successResponse(new AppointmentResource($updatedAppointment), 'تم تحديث الموعد بنجاح');
    }

    /**
     * DELETE /api/v1/appointments/{appointment}
     */
    public function destroy(Appointment $appointment)
    {
        $this->appointmentService->deleteAppointment($appointment);

        return $this->deletedResponse('تم حذف الموعد بنجاح');
    }

    /**
     * PATCH /api/v1/appointments/{appointment}/status
     */
    public function changeStatus(ChangeAppointmentStatusRequest $request, Appointment $appointment)
    {
        $updatedAppointment = $this->appointmentService->changeStatus(
            $appointment,
            $request->input('status')
        );

        return $this->successResponse(new AppointmentResource($updatedAppointment), 'تم تغيير حالة الموعد بنجاح');
    }
}
