<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = $user->notifications();

        // Apply filters
        if ($request->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($request->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate(20);

        // Get counts
        $totalCount = $user->notifications()->count();
        $unreadCount = $user->unreadNotifications()->count();
        $readCount = $user->readNotifications()->count();

        return view('notifications.index', compact(
            'notifications',
            'totalCount',
            'unreadCount',
            'readCount'
        ));
    }

    /**
     * Get notifications for API/AJAX requests.
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->data['type'] ?? 'info',
                    'title' => $notification->data['title'] ?? 'Notifikasi',
                    'message' => $notification->data['message'] ?? $notification->data['body'] ?? '',
                    'action_url' => $notification->data['action_url'] ?? null,
                    'action_text' => $notification->data['action_text'] ?? null,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->toISOString(),
                    'created_at_human' => $notification->created_at->diffForHumans(),
                ];
            });

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);

        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Notifikasi ditandai sebagai dibaca.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Semua notifikasi ditandai sebagai dibaca.');
    }

    /**
     * Delete a specific notification.
     */
    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);

        $notification->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Notifikasi berhasil dihapus.');
    }

    /**
     * Clear all notifications.
     */
    public function clearAll(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $user->notifications()->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Semua notifikasi berhasil dihapus.');
    }
}
