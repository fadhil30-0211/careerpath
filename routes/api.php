<?php

use App\Http\Controllers\Api\V1\Admin\CatalogController as AdminCatalogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Me\DevelopmentController;
use App\Http\Controllers\Api\V1\Me\MatchingController;
use App\Http\Controllers\Api\V1\Me\ProfileController;
use App\Http\Controllers\Api\V1\Public\CatalogController as PublicCatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::get('meta', [PublicCatalogController::class, 'meta']);
    Route::get('skills', [PublicCatalogController::class, 'skills']);
    Route::get('categories', [PublicCatalogController::class, 'categories']);
    Route::get('categories/{category}/children', [PublicCatalogController::class, 'categoryChildren']);
    Route::get('subcategories/{subcategory}/children', [PublicCatalogController::class, 'subcategoryChildren']);
    Route::get('occupations', [PublicCatalogController::class, 'occupations']);
    Route::get('occupations/{occupation}', [PublicCatalogController::class, 'occupation']);
});

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [ProfileController::class, 'show']);
    Route::put('me', [ProfileController::class, 'update']);

    Route::post('career-matching/runs', [MatchingController::class, 'run'])->middleware('throttle:20,1');
    Route::get('career-matching/runs/{matchingRun}', [MatchingController::class, 'show']);
    Route::get('me/analysis-history', [MatchingController::class, 'history']);

    Route::get('occupations/{occupation}/skill-gap', [DevelopmentController::class, 'skillGap']);
    Route::get('occupations/{occupation}/education-paths', [DevelopmentController::class, 'educationAndCertifications']);
    Route::get('me/saved-careers', [DevelopmentController::class, 'savedCareers']);
    Route::post('me/saved-careers/{occupation}', [DevelopmentController::class, 'saveCareer']);
    Route::delete('me/saved-careers/{occupation}', [DevelopmentController::class, 'unsaveCareer']);
    Route::get('me/roadmaps', [DevelopmentController::class, 'roadmaps']);
    Route::post('me/roadmaps', [DevelopmentController::class, 'startRoadmap']);
    Route::get('me/roadmaps/{roadmap}', [DevelopmentController::class, 'roadmap']);
    Route::patch('me/roadmaps/{roadmap}/stages/{stage}', [DevelopmentController::class, 'updateProgress']);
});

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('overview', [AdminCatalogController::class, 'overview']);
    Route::get('occupation-options', [AdminCatalogController::class, 'occupationOptions']);
    Route::get('reviews', [AdminCatalogController::class, 'reviews']);
    Route::get('users', [AdminCatalogController::class, 'users']);
    Route::patch('users/{id}', [AdminCatalogController::class, 'updateUser']);
    Route::get('audit-logs', [AdminCatalogController::class, 'auditLogs']);

    Route::get('occupations/{id}/requirements', [AdminCatalogController::class, 'requirements']);
    Route::put('occupations/{id}/requirements', [AdminCatalogController::class, 'updateRequirements']);
    Route::get('roadmaps/{id}/stages', [AdminCatalogController::class, 'roadmap']);
    Route::put('roadmaps/{id}/stages', [AdminCatalogController::class, 'updateRoadmap']);

    $resources = ['categories', 'subcategories', 'groups', 'occupations', 'skills', 'educations', 'certifications', 'industries', 'workplaces', 'sources', 'roadmaps'];
    Route::get('{resource}', [AdminCatalogController::class, 'index'])->whereIn('resource', $resources);
    Route::post('{resource}', [AdminCatalogController::class, 'store'])->whereIn('resource', $resources);
    Route::get('{resource}/{id}', [AdminCatalogController::class, 'show'])->whereIn('resource', $resources);
    Route::put('{resource}/{id}', [AdminCatalogController::class, 'update'])->whereIn('resource', $resources);
    Route::post('{resource}/{id}/transition', [AdminCatalogController::class, 'transition'])->whereIn('resource', $resources);
});
