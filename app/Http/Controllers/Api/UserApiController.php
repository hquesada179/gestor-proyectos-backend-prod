<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['plan', 'aiCredit']);
        $payload = $this->formatUser($user);

        return response()->json(array_merge($payload, [
            'data' => $payload,
        ]));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $payload = $this->normalizedProfilePayload($request);

        $validator = Validator::make($payload, [
            'name'  => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Los datos del perfil no son validos.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('email', $validated) && $validated['email'] !== $user->email) {
            $user->email = $validated['email'];
            $user->email_verified_at = null;
        }

        $user->save();
        $user->load(['plan', 'aiCredit']);

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'data'    => $this->formatUser($user),
        ]);
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        $file = $this->profilePhotoFile($request);

        $validator = Validator::make(['photo' => $file], [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'La imagen de perfil no es valida.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $file->store('profiles', 'public');
        $user->update(['profile_photo_path' => $path]);
        $user->load(['plan', 'aiCredit']);

        return response()->json([
            'message' => 'Foto de perfil actualizada correctamente',
            'data'    => $this->formatUser($user),
        ]);
    }

    public function deletePhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->update(['profile_photo_path' => null]);
        $user->load(['plan', 'aiCredit']);

        return response()->json([
            'message' => 'Foto de perfil eliminada correctamente',
            'data'    => $this->formatUser($user),
        ]);
    }

    private function formatUser($user): array
    {
        $photoUrl = $user->profile_photo_path
            ? Storage::disk('public')->url($user->profile_photo_path)
            : null;

        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'nombre'            => $user->name,
            'email'             => $user->email,
            'correo'            => $user->email,
            'firebase_uid'      => $user->firebase_uid,
            'provider'          => $user->firebase_uid ? 'google' : 'local',
            'auth_provider'     => $user->firebase_uid ? 'firebase' : 'local',
            'verified'          => $user->email_verified_at !== null,
            'profile_photo_path'=> $user->profile_photo_path,
            'avatar'            => $photoUrl,
            'photo_url'         => $photoUrl,
            'profile_photo_url' => $photoUrl,
            'imagen'            => $photoUrl,
            'image_url'         => $photoUrl,
            'plan_id'           => $user->plan_id,
            'plan'              => $user->plan ? [
                'id'                 => $user->plan->id,
                'name'               => $user->plan->name,
                'slug'               => $user->plan->slug,
                'monthly_price'      => $user->plan->monthly_price,
                'max_projects'       => $user->plan->max_projects,
                'monthly_ai_credits' => $user->plan->monthly_ai_credits,
            ] : null,
            'ai_credits' => $user->aiCredit ? [
                'available'      => $user->aiCredit->credits_available,
                'used'           => $user->aiCredit->credits_used,
                'period_ends_at' => $user->aiCredit->period_ends_at?->toDateString(),
            ] : null,
        ];
    }

    private function normalizedProfilePayload(Request $request): array
    {
        $payload = $request->all();

        if (!array_key_exists('name', $payload) && array_key_exists('nombre', $payload)) {
            $payload['name'] = $payload['nombre'];
        }

        if (!array_key_exists('email', $payload) && array_key_exists('correo', $payload)) {
            $payload['email'] = $payload['correo'];
        }

        return array_intersect_key($payload, array_flip(['name', 'email']));
    }

    private function profilePhotoFile(Request $request)
    {
        foreach (['photo', 'avatar', 'image', 'imagen', 'profile_photo'] as $field) {
            if ($request->hasFile($field)) {
                return $request->file($field);
            }
        }

        return null;
    }
}
