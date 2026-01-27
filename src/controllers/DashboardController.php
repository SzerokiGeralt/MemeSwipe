<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/UserStatsRepository.php';
require_once __DIR__.'/../repository/PostsRepository.php';
require_once __DIR__.'/../repository/QuestsRepository.php';

class DashboardController extends AppController {
    private $userRepository;
    private $userStatsRepository;
    private $postsRepository;
    private $questsRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->userStatsRepository = new UserStatsRepository();
        $this->postsRepository = new PostsRepository();
        $this->questsRepository = new QuestsRepository();
    }

    public function index(?string $id) {
        $userStats = null;
        $currentPost = null;
        $streakInfo = null;
        
        if (isset($_SESSION['user_id'])) {
            // Check and process streak first
            $streakInfo = $this->userStatsRepository->checkAndProcessStreak($_SESSION['user_id']);
            
            // Then get updated stats
            $userStats = $this->userStatsRepository->getStatsByUserId($_SESSION['user_id']);
            // Get a random post that user hasn't voted on yet
            $currentPost = $this->postsRepository->getRandomUnvotedPost($_SESSION['user_id']);
        } else {
            // For non-logged users, get any random post
            $currentPost = $this->postsRepository->getRandomPost();
        }

        return $this->render('dashboard', [
            'stats' => $userStats,
            'post' => $currentPost,
            'streakInfo' => $streakInfo
        ]);
    }

    public function vote() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);
        $postId = $data['post_id'] ?? null;
        $voteType = $data['vote_type'] ?? null; // 'upvote' or 'downvote'

        if (!$postId || !in_array($voteType, ['upvote', 'downvote'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid vote data']);
            return;
        }

        // Check if user already voted on this post
        if ($this->postsRepository->hasUserVoted($userId, $postId)) {
            echo json_encode(['success' => false, 'message' => 'Already voted on this post']);
            return;
        }

        // Register the vote
        $this->postsRepository->addVote($userId, $postId, $voteType);
        
        // Update post vote count
        $this->postsRepository->updateVoteCount($postId, $voteType);

        // Generate random rewards
        $expGained = rand(5, 25);
        $diamondsGained = rand(1, 10);
        
        // Bonus for upvoting
        if ($voteType === 'upvote') {
            $expGained += rand(0, 10);
            $diamondsGained += rand(0, 5);
        }

        // Update user experience and check for level up automatically
        $expUpdateResult = $this->userStatsRepository->updateExperience($userId, $expGained);
        $this->userStatsRepository->updateDiamonds($userId, $diamondsGained);

        // Process level up if it occurred
        $levelUpInfo = null;
        if ($expUpdateResult['leveledUp']) {
            $levelsGained = $expUpdateResult['levelsGained'];
            $bonusDiamonds = 0;
            
            // Calculate bonus diamonds for each level gained
            for ($i = 0; $i < $levelsGained; $i++) {
                $levelReached = $expUpdateResult['oldLevel'] + $i + 1;
                $bonusDiamonds += $levelReached * 50;
            }
            
            // Add bonus diamonds
            $this->userStatsRepository->updateDiamonds($userId, $bonusDiamonds);
            
            $levelUpInfo = [
                'leveled' => true,
                'newLevel' => $expUpdateResult['newLevel'],
                'levelsGained' => $levelsGained,
                'bonusDiamonds' => $bonusDiamonds
            ];
        }
        
        // Progress vote quests
        $completedQuests = $this->questsRepository->progressQuestsByAction($userId, 'vote', 1);
        
        // Progress streak quests
        $stats = $this->userStatsRepository->getStatsByUserId($userId);
        $this->questsRepository->progressQuestsByAction($userId, 'streak', $stats['streak']);
        
        // Check if the post author should have upvote quest progress
        if ($voteType === 'upvote') {
            $post = $this->postsRepository->getPostById($postId);
            if ($post) {
                $totalUpvotes = $this->postsRepository->getTotalUpvotesForUser($post['user_id']);
                $this->questsRepository->progressQuestsByAction($post['user_id'], 'upvote', $totalUpvotes);
            }
        }
        
        // Calculate quest rewards
        $questRewards = 0;
        foreach ($completedQuests as $completed) {
            $questRewards += $completed['reward'];
            $this->userStatsRepository->updateDiamonds($userId, $completed['reward']);
        }

        // Get next post
        $nextPost = $this->postsRepository->getRandomUnvotedPost($userId);

        // Get updated stats
        $updatedStats = $this->userStatsRepository->getStatsByUserId($userId);

        $response = [
            'success' => true,
            'message' => 'Vote registered!',
            'rewards' => [
                'exp' => $expGained,
                'diamonds' => $diamondsGained
            ],
            'levelUp' => $levelUpInfo,
            'stats' => [
                'level' => $updatedStats['level'],
                'diamonds' => $updatedStats['diamonds'],
                'streak' => $updatedStats['streak'],
                'expPercentage' => $updatedStats['expPercentage'],
                'expInCurrentLevel' => $updatedStats['expInCurrentLevel'],
                'expRequiredForNextLevel' => $updatedStats['expRequiredForNextLevel']
            ],
            'nextPost' => $nextPost
        ];
        
        if (!empty($completedQuests)) {
            $response['questsCompleted'] = count($completedQuests);
            $response['questRewards'] = $questRewards;
        }

        echo json_encode($response);
    }

    public function getNextPost() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            $post = $this->postsRepository->getRandomPost();
        } else {
            $post = $this->postsRepository->getRandomUnvotedPost($_SESSION['user_id']);
        }

        echo json_encode([
            'success' => true,
            'post' => $post
        ]);
    }
}