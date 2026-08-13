<?php

namespace App\Controllers;

use App\Libraries\GameRoomService;
use RuntimeException;

class GameRooms extends BaseController
{
    public function lobby(): string
    {
        return view('games/multiplayer', ['title' => 'Birlikte Oyna']);
    }

    public function create()
    {
        try {
            $room = (new GameRoomService())->create((int) session()->get('user_id'), (string) $this->request->getPost('game'), (string) $this->request->getPost('difficulty'));
            return redirect()->to(site_url('games/room/' . $room['code']));
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function join()
    {
        try {
            $room = (new GameRoomService())->join((int) session()->get('user_id'), (string) $this->request->getPost('code'));
            return redirect()->to(site_url('games/room/' . $room['code']));
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(string $code)
    {
        try {
            $room = (new GameRoomService())->getForPlayer($code, (int) session()->get('user_id'));
            return view('games/room', ['title' => 'Oyun Odası ' . $room['code'], 'room' => $room]);
        } catch (RuntimeException $e) {
            return redirect()->to(site_url('games/multiplayer'))->with('error', $e->getMessage());
        }
    }

    public function state(string $code)
    {
        try {
            $userId = (int) session()->get('user_id');
            session_write_close();
            return $this->response->setJSON(['success' => true, 'room' => (new GameRoomService())->getForPlayer($code, $userId)]);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function version(string $code)
    {
        try {
            $userId = (int) session()->get('user_id');
            session_write_close();
            return $this->response->setJSON(['success' => true] + (new GameRoomService())->versionForPlayer($code, $userId));
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function move(string $code)
    {
        try {
            $userId = (int) session()->get('user_id');
            session_write_close();
            $room = (new GameRoomService())->move($code, $userId, $this->request->getPost());
            return $this->response->setJSON(['success' => true, 'room' => $room, 'csrfHash' => csrf_hash()]);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => $e->getMessage(), 'csrfHash' => csrf_hash()]);
        }
    }
}
