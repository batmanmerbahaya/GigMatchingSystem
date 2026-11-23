<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Application;
use App\Models\Comment;
use App\Notifications\NewComment;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;

/**
 * UC19 Post Comment. Comments are plain text and cannot be edited or
 * deleted. The thread is read-only once payment is Confirmed. A comment can
 * reply to another one; the reply is shown under it, indented.
 */
class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Application $application): RedirectResponse
    {
        $comment = new Comment($request->validated());
        $comment->application_id = $application->id;
        $comment->user_id = $request->user()->id;

        // A reply belongs to the same submission as the comment it answers.
        if ($comment->parent_id !== null) {
            $comment->submission_id = Comment::query()->whereKey($comment->parent_id)->value('submission_id');
        }

        $comment->save();

        Notifier::send($application->otherParty($request->user()), new NewComment($comment));

        // Land on the new comment, which is highlighted (see :target in app.css).
        return redirect()
            ->to(route('applications.show', $application).'#comment-'.$comment->id)
            ->with('status', $comment->parent_id ? 'Reply posted.' : 'Comment posted.');
    }
}
