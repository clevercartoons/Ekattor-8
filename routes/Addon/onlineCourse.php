<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Addon\OnlineCourse;


//Online course
Route::controller(OnlineCourse::class)->group(function () {

	// Admin
    Route::get('admin/addons/courses', 'onlineCourses')->name('admin.addons.courses');
    Route::get('admin/addons/courses/course_add', 'courseAdd')->name('admin.addons.course_add');
    Route::post('admin/addons/courses/course/create', 'courseCreate')->name('admin.addons.course_create');
    Route::get('admin/addons/courses/course_edit/{id}', 'courseEdit')->name('admin.addons.course_edit');
    Route::get('admin/addons/courses/course_edit/section_add/{id}', 'sectionAdd')->name('admin.addons.section_add');
    Route::post('admin/addons/courses/course_edit/{id}', 'sectionCreate')->name('admin.addons.section_create');
    Route::get('admin/addons/courses/course_edit/lesson_add/{id}', 'lessonAdd')->name('admin.addons.lesson_add');


});