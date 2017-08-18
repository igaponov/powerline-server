<?php

namespace Tests\Civix\ApiBundle\Controller\V2_2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\BaseCommentRate;
use Civix\CoreBundle\Entity\User;
use PHPUnit\Framework\TestCase;

trait CommentControllerTestTrait
{
    /**
     * @param BaseComment $comment
     * @param User $owner
     * @param array $commentData
     */
    protected function assertComment(BaseComment $comment, User $owner, array $commentData): void
    {
        /** @var TestCase $this */
        $this->assertCount(14, $commentData);
        $this->assertSame($comment->getPrivacyLabel(), $commentData['privacy']);
        $this->assertSame($comment->getId(), $commentData['id']);
        $this->assertSame($comment->getCommentBody(), $commentData['comment_body']);
        $this->assertSame($comment->getCommentBodyHtml(), $commentData['comment_body_html']);
        $this->assertNotEmpty($commentData['created_at']);
        $this->assertSame($comment->getRateSum(), $commentData['rate_sum']);
        $this->assertSame($comment->getRatesCount(), $commentData['rate_count']);
        $rate = $comment->getRates()->filter(function (BaseCommentRate $rate) use ($owner) {
            return $rate->getUser()->isEqualTo($owner);
        })->get(0);
        if ($rate) {
            $this->assertSame($rate->getRateValueLabel(), $commentData['rate_value']);
        } else {
            $this->assertEmpty($commentData['rate_value']);
        }
        $this->assertSame($comment->getUser()->isEqualTo($owner), $commentData['is_owner']);
        $this->assertSame($comment->getParentId(), $commentData['parent_comment']);
        $user = $comment->getUser();
        if ($comment->isPrivate()) {
            $this->assertContains(User::SOMEONE_AVATAR, $commentData['author_picture']);
            $this->assertNull($commentData['user']);
        } else {
            $this->assertContains($user->getAvatarFileName(), $commentData['author_picture']);
            $userData = $commentData['user'];
            $this->assertCount(7, $userData);
            $this->assertSame($user->getFullName(), $userData['full_name']);
            $this->assertSame($user->getId(), $userData['id']);
            $this->assertSame($user->getUsername(), $userData['username']);
            $this->assertSame($user->getFirstName(), $userData['first_name']);
            $this->assertSame($user->getLastName(), $userData['last_name']);
            $this->assertContains($user->getAvatarFileName(), $userData['avatar_file_name']);
        }
        $children = $comment->getChildren()->slice(0, 2);
        $this->assertCount(count($children), $commentData['children']);
        foreach ($children as $key => $child) {
            $this->assertComment($child, $owner, $commentData['children'][$key]);
        }
        $this->assertSame($comment->getChildren()->count(), $commentData['child_count']);
    }
}