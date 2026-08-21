<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\Services\AdminChatService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexChatMessagesRequest;
use Inertia\Inertia;
use Inertia\Response;

class ChatMessageController extends Controller
{
    public function __construct(private readonly AdminChatService $chat) {}

    public function __invoke(IndexChatMessagesRequest $request): Response
    {
        return Inertia::render('Admin/Chat/Index', $this->chat->paginatedMessages($request->filters()));
    }
}
