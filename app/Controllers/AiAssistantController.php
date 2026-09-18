<?php

namespace App\Controllers;

use App\Core\AiNeedsAssistant;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;

class AiAssistantController extends Controller
{
    public function index(): void
    {
        Permission::requireCapability('needs_assessments', 'view');
        $analysis = AiNeedsAssistant::analyze(Database::connection());
        $this->view('ai/index', ['title' => 'AI Needs Assessment Assistant', 'a' => $analysis]);
    }
}
