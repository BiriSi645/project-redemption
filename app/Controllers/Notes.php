<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\NoteModel;
use CodeIgniter\HTTP\ResponseInterface;

class Notes extends BaseController
{
    public function index()
    {
        $noteModel = new NoteModel();

        $data = [
            'notes' => $noteModel->findAll(),
        ];

        return view('notes/index', $data);
    }

    public function create()
    {
        return view('notes/create');
    }

    public function store()
    {    
        $rules = [
            'title'   => 'required|min_length[2]',
            'content' => 'required|min_length[2]',
        ];

        if (! $this->validate($rules)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
            }
        $noteModel = new NoteModel();

        $title = $this->request->getPost('title');
        $content = $this->request->getPost('content');
        
        $noteModel->insert([
            'title' => $title,
            'content' => $content,
        ]);

        return redirect()->to('/notes');
    }

    public function edit($id)
    {
        $noteModel = new NoteModel();

        $note = $noteModel->find($id);

        if (!$note) {
            return redirect()->to('/notes');
        }

        $data = [
            'note' => $note,
        ];

        return view('notes/edit', $data);
    }

    public function update($id)
    {
        $rules = [
            'title'   => 'required|min_length[2]',
            'content' => 'required|min_length[2]',
        ];

        if (! $this->validate($rules)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $noteModel = new NoteModel();

        $noteModel->update($id, [
            'title'   => $this->request->getPost('title'),
            'content' => $this->request->getPost('content'),
        ]);

        return redirect()->to('/notes');
    }

    public function delete($id)
    {
        $noteModel = new NoteModel();

        $noteModel->delete($id);

        return redirect()->to('/notes');
    }

}
