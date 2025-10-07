<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\AudioService;
use App\Services\RecitationService;

class AudioController {
    private $audioService;
    private $recitationService;

    public function __construct(AudioService $audioService, RecitationService $recitationService) {
        $this->audioService = $audioService;
        $this->recitationService = $recitationService;
    }

    public function getChapterAudio(Request $request, Response $response, array $args): Response {
        $reciterId = (int)$args['id'];
        $chapterNumber = (int)$args['chapter_number'];
        $segments = filter_var($request->getQueryParams()['segments'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        $audio = $this->audioService->getChapterAudio($reciterId, $chapterNumber, $segments);
        
        if (!$audio) {
            $response->getBody()->write(json_encode(['error' => 'Audio file not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode(['audio_file' => $audio]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getReciterAudioFiles(Request $request, Response $response, array $args): Response {
        $reciterId = (int)$args['id'];
        $language = $request->getQueryParams()['language'] ?? 'en';
        
        $files = $this->audioService->getReciterAudioFiles($reciterId, $language);
        
        $response->getBody()->write(json_encode(['audio_files' => $files]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getRecitationAudioFiles(Request $request, Response $response, array $args): Response {
        $recitationId = (int)$args['recitation_id'];
        $params = $request->getQueryParams();
        
        $filters = [
            'chapter_number' => $params['chapter_number'] ?? null,
            'juz_number' => $params['juz_number'] ?? null,
            'page_number' => $params['page_number'] ?? null,
            'hizb_number' => $params['hizb_number'] ?? null,
            'rub_el_hizb_number' => $params['rub_el_hizb_number'] ?? null,
        ];
        
        $fields = isset($params['fields']) ? explode(',', $params['fields']) : [];
        
        $result = $this->audioService->getRecitationAudioFiles($recitationId, $filters, $fields);
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getSurahAyahRecitations(Request $request, Response $response, array $args): Response {
        $recitationId = (int)$args['recitation_id'];
        $chapterNumber = (int)$args['chapter_number'];
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 10);
        
        $result = $this->audioService->getAyahRecitations($recitationId, 'surah', $chapterNumber, $page, $perPage);
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getJuzAyahRecitations(Request $request, Response $response, array $args): Response {
        $recitationId = (int)$args['recitation_id'];
        $juzNumber = (int)$args['juz_number'];
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 10);
        
        $result = $this->audioService->getAyahRecitations($recitationId, 'juz', $juzNumber, $page, $perPage);
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getPageAyahRecitations(Request $request, Response $response, array $args): Response {
        $recitationId = (int)$args['recitation_id'];
        $pageNumber = (int)$args['page_number'];
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 10);
        
        $result = $this->audioService->getAyahRecitations($recitationId, 'page', $pageNumber, $page, $perPage);
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getRubElHizbAyahRecitations(Request $request, Response $response, array $args): Response {
        $recitationId = (int)$args['recitation_id'];
        $rubElHizbNumber = (int)$args['rub_el_hizb_number'];
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 10);
        
        $result = $this->audioService->getAyahRecitations($recitationId, 'rub_el_hizb', $rubElHizbNumber, $page, $perPage);
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getHizbAyahRecitations(Request $request, Response $response, array $args): Response {
        $recitationId = (int)$args['recitation_id'];
        $hizbNumber = (int)$args['hizb_number'];
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 10);
        
        $result = $this->audioService->getAyahRecitations($recitationId, 'hizb', $hizbNumber, $page, $perPage);
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getAyahRecitations(Request $request, Response $response, array $args): Response {
        $recitationId = (int)$args['recitation_id'];
        $ayahKey = $args['ayah_key'];
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 10);

        $result = $this->audioService->getAyahRecitations($recitationId, 'ayah', $ayahKey, $page, $perPage);

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getById(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $audioFile = $this->audioService->getById($id);

        if (!$audioFile) {
            $response->getBody()->write(json_encode(['error' => 'Audio file not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode(['audio_file' => $audioFile]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);

        $result = $this->audioService->create($data);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $response->getBody()->write(json_encode(['audio_file' => $result]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function update(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        $result = $this->audioService->update($id, $data);

        if (isset($result['error'])) {
            $statusCode = ($result['error'] === 'Audio file not found') ? 404 : 400;
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
        }

        $response->getBody()->write(json_encode(['audio_file' => $result]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $id = $args['id'];

        $result = $this->audioService->delete($id);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
