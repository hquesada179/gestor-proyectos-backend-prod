<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('asistente-ia.index');
    }

    public function sendMessage(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return $this->legacyJsonRedirect();
        }

        return redirect()->route('asistente-ia.index');
    }

    public function history(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return $this->legacyJsonRedirect();
        }

        return redirect()->route('asistente-ia.index');
    }

    public function clearHistory(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return $this->legacyJsonRedirect();
        }

        return redirect()->route('asistente-ia.index');
    }

    public function deleteHistoryItem(Request $request, int $id): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return $this->legacyJsonRedirect();
        }

        return redirect()->route('asistente-ia.index');
    }

    private function legacyJsonRedirect(): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'redirect' => route('asistente-ia.index'),
            'message' => 'Este endpoint ya no está disponible. Usa el nuevo asistente IA.',
        ], 410);
    }
}
