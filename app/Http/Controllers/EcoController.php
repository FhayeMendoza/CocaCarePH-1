<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Ecotrack;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class EcoController extends Controller
{

    public function index(){
        $ecotrackers = Ecotrack::paginate(10);
        return view('backend.tracker.index', compact('ecotrackers'));
    }

    public function edit($id)
    {
        $ecotrackers=Ecotrack::find($id);
        return view('backend.tracker.edit', compact('ecotrackers'));
    }

    public function update(Request $request, $id)
{
    $ecotrackers = Ecotrack::find($id);
    $this->validate($request, [
        'status' => 'required|in:complete,failed'
    ]);

    // Assuming $order is supposed to be $ecotrackers, as it's not defined in your code snippet
    $data = $request->all();

    // Update the status
    $ecotrackers->status = $request->status;

    // Save the updated data
    $status = $ecotrackers->save();

    if ($status) {
        if ($request->status == 'complete') {
            // Flash the announcement message for the user's homepage
            $announcement = 'Congratulations! You have successfully completed a task.';
            session()->flash('announcement', $announcement);
            dd(session('announcement'));
        }
        request()->session()->flash('success', 'Successfully updated task status');
    } else {
        request()->session()->flash('error', 'Error while updating task status');
    }

    return redirect()->route('tracker.index');
}


    public function store(Request $request)
    {
        // Check if the user has already submitted today
        if (session('form_submitted_today')) {
            $existingSubmission = Ecotrack::where('name', $request->input('name'))
                ->whereDate('created_at', Carbon::yesterday())
                ->first();

            if ($existingSubmission) {
                // Update the data from yesterday to today
                $existingSubmission->task_name = $request->input('task_name');
                $existingSubmission->task_description = $request->input('task_description') ?? 'No task description provided';
                $existingSubmission->date = $request->input('date');
                $existingSubmission->tasks = json_encode($request->input('task'));

                $existingSubmission->save();

                return redirect()->route('ecotracker')->with('success', 'Form updated successfully!');
            }
        }

        // Proceed with storing the submission
        $this->validate($request, [
            'name' => 'required|string',
            'task_name' => 'required|string',
            'task_description' => 'required|string',
            'date' => 'required|date',
            'task' => 'required|array',
            'task.*' => 'string',
        ]);

        $ecotrackers = new Ecotrack();
        $ecotrackers->user_id = Auth::id(); // Set the user_id
        $ecotrackers->name = $request->input('name');
        $ecotrackers->task_name = $request->input('task_name');
        $ecotrackers->task_description = $request->input('task_description') ?? 'No task description provided';
        $ecotrackers->date = $request->input('date');
        $ecotrackers->tasks = json_encode($request->input('task'));

        $ecotrackers->save();

        // Set session variable to mark form submission for today
        Session::put('form_submitted_today', true);

        // Check if the user already exists in the database
        $user = Ecotrack::where('name', $request->input('name'))->first();
        if ($user) {
            // Update user's answer count and last answered date
            $user->tasks;
            $user->answer_count++;
            $user->last_answered_date = Carbon::today();
            $user->save();
        } else {
            // Create a new user record and set answer count to 1
            $newUser = new Ecotrack();
            $newUser->name = $request->input('name');
            $newUser->answer_count = 1;
            $newUser->last_answered_date = Carbon::today();
            $newUser->save();
        }

        return redirect()->route('ecotracker')->with('success', 'Form submitted successfully!');
    }



    public function show(Request $request,$id)
    {
        $ecotrackers=Ecotrack::find($id);
        // return $order;
        return view('backend.tracker.show', compact('ecotrackers'));
    }

    public function destroy($id)
    {
        $ecotrackers=Ecotrack::find($id);
        $status=$ecotrackers->delete();
        if($status){
            request()->session()->flash('success','Deleted Eco-track Data Successfully!');
        }
        else{
            request()->session()->flash('error','Error occurred please try again.');
        }
        return back();
    }
}
