<?php

require_once __DIR__ . '/../repository/UserStatsRepository.php';

class AppController {
    
    /**
     * Check and process streak for logged in user
     * Returns streak info for popup display
     */
    protected function checkAndProcessStreak(): ?array {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        // Check if streak was already processed in this session today
        $today = date('Y-m-d');
        if (isset($_SESSION['streak_checked_date']) && $_SESSION['streak_checked_date'] === $today) {
            return null; // Already processed today, don't show popup again
        }
        
        $userStatsRepository = new UserStatsRepository();
        $streakInfo = $userStatsRepository->checkAndProcessStreak($_SESSION['user_id']);
        
        // Mark streak as checked for today
        $_SESSION['streak_checked_date'] = $today;
        
        return $streakInfo;
    }

    protected function render(string $template = null, array $variables = [])
    {
        $templatePath = 'public/views/'. $template.'.html';
        $templatePath404 = 'public/views/404.html';
        $output = "";
        
        // Check and process streak for all pages if user is logged in
        if (!isset($variables['streakInfo'])) {
            $streakInfo = $this->checkAndProcessStreak();
            if ($streakInfo) {
                $variables['streakInfo'] = $streakInfo;
            }
        }
                 
        if(file_exists($templatePath)){
            // ["message" => "Hello World"]
            extract($variables);
            // $message = "Hello World"
            // echo message
            ob_start();
            include $templatePath;
            $output = ob_get_clean();
        } else {
            ob_start();
            include $templatePath404;
            $output = ob_get_clean();
        }
        echo $output;
    }

}