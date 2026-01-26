<?php
require_once 'AppController.php';
require_once __DIR__ . '/../repository/LeadersRepository.php';
require_once __DIR__ . '/../repository/UserStatsRepository.php';

class LeadersController extends AppController {
    private $leadersRepository;
    private $userStatsRepository;

    public function __construct() {
        $this->leadersRepository = new LeadersRepository();
        $this->userStatsRepository = new UserStatsRepository();
    }

    public function index(?string $id) {
        if (!isset($_SESSION['user_id'])) {
            // Not logged in - redirect to login page
            header('Location: /login');
            exit();
        }

        // Get current user stats for navbar (with expPercentage calculated)
        $userStats = $this->userStatsRepository->getStatsByUserId($_SESSION['user_id']);

        // Get period from query parameter, default to 'all'
        $period = $_GET['period'] ?? 'all';
        
        // Validate period
        if (!in_array($period, ['weekly', 'monthly', 'yearly', 'all'])) {
            $period = 'all';
        }

        // Get leaderboard data
        $leaders = $this->leadersRepository->getTopUsersByUpvotes($period);

        return $this->render('leaders', [
            'leaders' => $leaders,
            'period' => $period,
            'stats' => $userStats
        ]);
    }
}