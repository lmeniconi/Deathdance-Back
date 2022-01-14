<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

use App\Models\Appointment;

class AppointmentController extends Controller
{
    /**
     * @OA\Get(
     *      path="/appointments",
     *      operationId="getAppointmentsList",
     *      tags={"Appointments"},
     *      summary="Get list of appointments",
     *      description="Returns list of appointments",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *     )
     */
    public function index(Request $request)
    {
        if ($request->query()['date'] ?? false) {
            return $this->getAppointmentsInDate($request->query()['date']);
        }
        return Appointment::all()
            ->transform(fn ($appointment) => [
            'id' =>$appointment->id,
            'name' => $appointment->name,
            'email' => $appointment->email,
            'start' => $appointment->start
            ]);
    }

     /**
     * @OA\Post(
     *      path="/appointments",
     *      operationId="storeAppointment",
     *      tags={"Appointments"},
     *      summary="Store new appointment",
     *      description="Returns appointment data",
     *      @OA\RequestBody(
     *          required=true,
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(Appointment::rules());
        $appointment = Appointment::create($request->all());
        return $appointment;
    }

     /**
     * @OA\Get(
     *      path="/appointments/{id}",
     *      operationId="getAppointmentById",
     *      tags={"Appointments"},
     *      summary="Get appointment information",
     *      description="Returns appointment data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Appointment id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function show($id)
    {
        $appointment = $this->getAppointmentById($id);
        return $appointment;
    }

    /**
     * @OA\Put(
     *      path="/appointments/{id}",
     *      operationId="updateAppointment",
     *      tags={"Appointments"},
     *      summary="Update existing appointment",
     *      description="Returns updated appointment data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Appointment id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function update(Request $request, $id)
    {
        $appointment = $this->getAppointmentById($id);
        
        $rules = Appointment::rules();
        if($request->all()['start'] == $appointment->start) {
            array_pop($rules);
        }
        $request->validate($rules);
        $appointment->update($request->all());
        return $appointment;
    }

     /**
     * @OA\Delete(
     *      path="/appointments/{id}",
     *      operationId="deleteAppointment",
     *      tags={"Appointments"},
     *      summary="Delete existing appointment",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Appointment id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function destroy($id)
    {
        $appointment = $this->getAppointmentById($id);
        $appointment->delete();
    }

    /**
     * @OA\Get(
     *      path="/appointments/{date}/hours",
     *      operationId="getHoursList",
     *      tags={"Appointments"},
     *      summary="Get list of available hours",
     *      description="Returns list of available hours in a  date",
     *      @OA\Parameter(
     *          name="date",
     *          description="Date",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *     )
     */
    public function availableHours(Request $request, $date)
    {
        $appointments = $this->getAppointmentsInDate($date);
        $hours = Config::get('hours.valid_hours');

        // Filter available hours
        foreach ($appointments as $appointment) {
            $aux = explode(" ", $appointment['start'])[1];
            $pos = array_search($aux, $hours);
            if ($pos || $pos == 0) {
                unset($hours[$pos]);
            }
        }

        // Filter hours if the date is today
        if (date($date) == date('Y-m-d')) {
            $now = date('H:i:s');
            foreach ($hours as $pos => $hour) {
                if (date($hour) <= $now) {
                    unset($hours[$pos]);
                }
            }
        }
        
        // Format array
        $availableHours = array();
        foreach ($hours as $hour) {
            array_push($availableHours, $hour);
        }

        return $availableHours;
    }

    public function getAppointmentById($id) {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            abort(404);
        }
        return $appointment;
    }
    
    public function getAppointmentsInDate($date) {
        $start = $date." ".'00:00:00';
        $end = $date." ".'23:00:00';
        return Appointment::where('start', '>=', $start)
            ->where('start', '<=', $end)
            ->get()
            ->transform(fn ($appointment) => [
            'id' =>$appointment->id,
            'name' => $appointment->name,
            'email' => $appointment->email,
            'start' => $appointment->start
            ]);

    }
}
