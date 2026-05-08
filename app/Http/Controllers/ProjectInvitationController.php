<?php

namespace App\Http\Controllers;

use App\Models\ProjectInvitation;
use App\Models\ProjectMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectInvitationController extends Controller
{
    /** List of pending invitations for the authenticated user. */
    public function index(): View
    {
        $invitations = ProjectInvitation::where('invited_user_id', Auth::id())
            ->with(['proyecto.user', 'invitedBy', 'role'])
            ->latest()
            ->get();

        return view('invitations.index', compact('invitations'));
    }

    /** Accept an invitation. */
    public function accept(ProjectInvitation $invitation): RedirectResponse
    {
        abort_if($invitation->invited_user_id !== Auth::id(), 403);
        abort_if(!$invitation->isPending(), 400);

        DB::transaction(function () use ($invitation) {
            $user = Auth::user();

            // Create or update the project_member record
            $invitation->proyecto->members()->updateOrCreate(
                ['email' => $invitation->email],
                [
                    'user_id'  => $user->id,
                    'name'     => $user->name,
                    'email'    => $invitation->email,
                    'role_id'  => $invitation->role_id,
                    'status'   => 'activo',
                ]
            );

            // Mark invitation as accepted
            $invitation->update([
                'status'       => 'accepted',
                'responded_at' => now(),
            ]);
        });

        return redirect()->route('proyectos.index')
            ->with('success', "Te uniste al proyecto «{$invitation->proyecto->nombre}» correctamente.");
    }

    /** Reject an invitation. */
    public function reject(ProjectInvitation $invitation): RedirectResponse
    {
        abort_if($invitation->invited_user_id !== Auth::id(), 403);
        abort_if(!$invitation->isPending(), 400);

        DB::transaction(function () use ($invitation) {
            // Mark invitation as rejected
            $invitation->update([
                'status'       => 'rejected',
                'responded_at' => now(),
            ]);

            // Remove the placeholder member record if still invitado
            $invitation->proyecto->members()
                ->where('email', $invitation->email)
                ->where('status', 'invitado')
                ->whereNull('user_id')
                ->delete();
        });

        return back()->with('info', 'Invitación rechazada.');
    }
}
