<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\Services\AdminPublicRoomService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexPublicRoomsRequest;
use Inertia\Inertia;
use Inertia\Response;

class PublicRoomController extends Controller
{
    public function __construct(private readonly AdminPublicRoomService $rooms) {}

    public function index(IndexPublicRoomsRequest $request): Response
    {
        return Inertia::render('Admin/Rooms/Public/Index', $this->rooms->paginatedRooms($request->filters()));
    }

    public function show(string $room): Response
    {
        return Inertia::render('Admin/Rooms/Public/Show', $this->rooms->roomDetails($room));
    }
}
