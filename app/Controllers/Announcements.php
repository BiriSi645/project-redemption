<?php

namespace App\Controllers;

use App\Models\AnnouncementModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Announcements extends BaseController
{
    public function show(int $id): string
    {
        $announcement = (new AnnouncementModel())->withAuthor()->find($id);
        if (! $announcement) {
            throw PageNotFoundException::forPageNotFound('Duyuru bulunamadı.');
        }

        return view('announcements/show', [
            'title' => $announcement['title'],
            'announcement' => $announcement ,
        ]);
    }
}
