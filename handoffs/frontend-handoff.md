# Quran Audio API - Frontend Handoff Guide

## Overview

This guide provides comprehensive documentation for consuming the Quran Audio API in a Vue 3 + TypeScript + Quasar frontend application. The API provides access to Quran recitations, audio files, tafseer (commentary), and Cloudinary integration for audio management.

## Handoff Document Location

**IMPORTANT:** All frontend handoff documents must be created in:
```
handoffs/
```

When creating feature-specific handoffs (authentication, audio, tafseer, etc.), always:
1. Create a new `.md` file in the `handoffs/` directory
2. Name it descriptively: `{feature}-handoff.md` (e.g., `authentication-handoff.md`, `audio-handoff.md`)
3. Include complete API documentation, TypeScript interfaces, services, composables, and testing commands
4. Follow the structure and patterns defined in this guide

## Architecture Pattern

```
Pages/Components → Composables → Services → API (PHP Backend)
```

**Rules:**
- ✅ Pages/Components use Composables only
- ✅ Composables call Services
- ✅ Services handle API calls
- ❌ Pages/Components must NOT call Services directly
- ❌ Pages/Components must NOT make API calls directly

---

## API Base Configuration

### Backend API Structure

**Base URL:** `http://localhost/QuranAudio/`
All endpoints follow the pattern: `{BASE_URL}/{resource}`

### Authentication

**JWT Token-Based Authentication**

**Login Endpoint:**
```
POST /auth/login
Content-Type: application/json

{
  "username": "admin",
  "password": "admin123"
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": "user_123",
    "username": "admin",
    "email": "admin@example.com",
    "role_slug": "admin",
    "role_name": "Administrator"
  }
}
```

**Token Usage:**
- Store token in localStorage: `auth_token`
- Add to all authenticated requests: `Authorization: Bearer {token}`
- Token refresh on 401 errors

---

## API Endpoints Reference

### Authentication Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/auth/register` | User registration | No |
| POST | `/auth/login` | User login | No |
| GET | `/auth/test-protected` | Test JWT protection | Yes |
| GET | `/auth/test-admin` | Test Admin role | Yes |
| GET | `/auth/test-superadmin` | Test Superadmin role | Yes |

### Reciter Management Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/chapter-reciters` | List all reciters | No |
| GET | `/chapter-reciters/{id}` | Get reciter by ID | No |
| POST | `/chapter-reciters` | Create reciter | Yes |
| PUT | `/chapter-reciters/{id}` | Update reciter | Yes |
| DELETE | `/chapter-reciters/{id}` | Delete reciter | Yes |

### Recitation Management Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/recitations` | List all recitations | No |
| GET | `/recitations/{id}` | Get recitation by ID | No |
| POST | `/recitations` | Create recitation | Yes |
| PUT | `/recitations/{id}` | Update recitation | Yes |
| DELETE | `/recitations/{id}` | Delete recitation | Yes |

### Audio File Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/audio-files/{id}` | Get audio file by ID | No |
| POST | `/audio-files` | Create audio file | Yes |
| PUT | `/audio-files/{id}` | Update audio file | Yes |
| DELETE | `/audio-files/{id}` | Delete audio file | Yes |
| GET | `/reciters/{id}/chapters/{chapter_number}` | Get chapter audio | No |
| GET | `/reciters/{id}/audio-files` | Get reciter audio files | No |
| GET | `/recitation-audio-files/{recitation_id}` | Get recitation audio files | No |

### Resource Recitation Endpoints

| Method | Endpoint | Description | Auth Required | Pagination |
|--------|----------|-------------|---------------|------------|
| GET | `/resources/recitations/{recitation_id}/{chapter_number}` | Get surah ayah recitations | No | ✅ |
| GET | `/resources/recitations/{recitation_id}/juz/{juz_number}` | Get juz ayah recitations | No | ✅ |
| GET | `/resources/recitations/{recitation_id}/pages/{page_number}` | Get page ayah recitations | No | ✅ |
| GET | `/resources/recitations/{recitation_id}/rub-el-hizb/{rub_el_hizb_number}` | Get rub el hizb ayah recitations | No | ✅ |
| GET | `/resources/recitations/{recitation_id}/hizb/{hizb_number}` | Get hizb ayah recitations | No | ✅ |
| GET | `/resources/ayah-recitation/{recitation_id}/{ayah_key}` | Get ayah recitation | No | ❌ |

### Tafseer (Commentary) Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/tafseers` | List all tafseers | No |
| GET | `/tafseers/{id}` | Get tafseer by ID | No |
| GET | `/tafseers/{id}/audio-files` | Get tafseer audio files | No |
| GET | `/tafseers/verses/{verse_from}` | Get tafseer by verse | No |
| GET | `/tafseers/verses/{verse_from}/{verse_to}` | Get tafseer by verse range | No |
| POST | `/tafseers` | Create tafseer | Yes |
| PUT | `/tafseers/{id}` | Update tafseer | Yes |
| DELETE | `/tafseers/{id}` | Delete tafseer | Yes |

### Mufasser (Commentator) Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/mufassers` | List all mufassers | No |
| GET | `/mufassers/{id}` | Get mufasser by ID | No |
| GET | `/mufassers/{id}/tafseers` | Get mufasser tafseers | No |
| POST | `/mufassers` | Create mufasser | Yes |
| PUT | `/mufassers/{id}` | Update mufasser | Yes |
| DELETE | `/mufassers/{id}` | Delete mufasser | Yes |

### Audio Tafseer Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/audio-tafseers/{id}` | Get audio tafseer by ID | No |
| GET | `/audio-tafseers/verses/{verse_from}` | Get audio tafseer by verse | No |
| GET | `/audio-tafseers/verses/{verse_from}/{verse_to}` | Get audio tafseer by verse range | No |
| POST | `/audio-tafseers` | Create audio tafseer | Yes |
| PUT | `/audio-tafseers/{id}` | Update audio tafseer | Yes |
| DELETE | `/audio-tafseers/{id}` | Delete audio tafseer | Yes |

### Cloudinary Integration Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/cloudinary/test` | Test Cloudinary connection | No |
| GET | `/cloudinary/stats` | Get usage statistics | Yes |
| POST | `/cloudinary/tafseer-audio` | Upload tafseer audio | Yes |
| GET | `/cloudinary/tafseer-audio/{id}` | Get tafseer audio with qualities | No |
| PUT | `/cloudinary/tafseer-audio/{id}` | Update tafseer audio | Yes |
| DELETE | `/cloudinary/tafseer-audio/{id}` | Delete tafseer audio | Yes |
| POST | `/cloudinary/tafseer-audio/batch` | Batch upload audio | Yes |
| POST | `/cloudinary/tafseer-audio/{id}/migrate` | Migrate to Cloudinary | Yes |
| GET | `/cloudinary/audio-info/{public_id}` | Get audio info | Yes |

---

## Server-Side Pagination

### Overview

The backend API supports server-side pagination for endpoints that handle large datasets. This enables efficient data loading and improves frontend performance.

### Pagination Parameters

All paginated endpoints support these query parameters:

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | number | 1 | Page number |
| `per_page` | number | 10 | Number of records per page (max: 100) |

### Pagination Response Format

All paginated endpoints return data in this standardized format:

```json
{
  "audio_files": [
    // Array of records
  ],
  "pagination": {
    "per_page": 10,
    "current_page": 1,
    "next_page": 2,
    "total_pages": 5,
    "total_records": 50
  }
}
```

### Paginated Endpoints

#### Resource Recitation Endpoints

**Surah Ayah Recitations**
```
GET /resources/recitations/{recitation_id}/{chapter_number}?page=1&per_page=10
```

**Juz Ayah Recitations**
```
GET /resources/recitations/{recitation_id}/juz/{juz_number}?page=1&per_page=10
```

**Page Ayah Recitations**
```
GET /resources/recitations/{recitation_id}/pages/{page_number}?page=1&per_page=10
```

**Rub el Hizb Ayah Recitations**
```
GET /resources/recitations/{recitation_id}/rub-el-hizb/{rub_el_hizb_number}?page=1&per_page=10
```

**Hizb Ayah Recitations**
```
GET /resources/recitations/{recitation_id}/hizb/{hizb_number}?page=1&per_page=10
```

---

## Data Models & Interfaces

### Authentication Interfaces

```typescript
export interface IUser {
  id: string;
  username: string;
  email: string;
  role_slug: string;
  role_name: string;
  created_at: string;
  updated_at: string;
}

export interface ILoginCredentials {
  username: string;
  password: string;
}

export interface ILoginResponse {
  token: string;
  user: IUser;
}

export interface IRegisterData {
  username: string;
  email: string;
  password: string;
  role_id?: string;
}
```

### Reciter Interfaces

```typescript
export interface IReciter {
  id: number;
  name: string;
  arabic_name: string;
  relative_path: string;
  format: string;
  files_size: number;
  translated_name?: {
    name: string;
    language_name: string;
  };
  created_at: string;
  updated_at: string;
}

export interface IReciterCreateData {
  name: string;
  arabic_name: string;
  relative_path: string;
  format: string;
  files_size: number;
}
```

### Recitation Interfaces

```typescript
export interface IRecitation {
  id: number;
  reciter_name: string;
  style: string;
  translated_name?: {
    name: string;
    language_name: string;
  };
  created_at: string;
  updated_at: string;
}

export interface IRecitationCreateData {
  reciter_name: string;
  style: string;
  translated_name?: object;
}
```

### Audio File Interfaces

```typescript
export interface IAudioFile {
  id: number;
  chapter_id: number;
  file_size: number;
  format: string;
  audio_url: string;
  duration: number;
  verse_key?: string;
  juz_number?: number;
  page_number?: number;
  hizb_number?: number;
  rub_el_hizb_number?: number;
  timestamps?: ITimestamp[];
  created_at: string;
  updated_at: string;
}

export interface ITimestamp {
  verse_key: string;
  timestamp_from: number;
  timestamp_to: number;
  duration: number;
  segments?: number[][];
}

export interface IAudioFileCreateData {
  chapter_id: number;
  file_size: number;
  format: string;
  audio_url: string;
  duration: number;
  verse_key?: string;
  juz_number?: number;
  page_number?: number;
  hizb_number?: number;
  rub_el_hizb_number?: number;
}
```

### Tafseer Interfaces

```typescript
export interface ITafseer {
  id: number;
  mufasser_id: number;
  verse_from: string;
  verse_to: string;
  text: string;
  language: string;
  mufasser?: IMufasser;
  created_at: string;
  updated_at: string;
}

export interface ITafseerCreateData {
  mufasser_id: number;
  verse_from: string;
  verse_to: string;
  text: string;
  language: string;
}

export interface IMufasser {
  id: number;
  name: string;
  arabic_name: string;
  biography?: string;
  language: string;
  created_at: string;
  updated_at: string;
}

export interface IMufasserCreateData {
  name: string;
  arabic_name: string;
  biography?: string;
  language: string;
}
```

### Audio Tafseer Interfaces

```typescript
export interface IAudioTafseer {
  id: number;
  tafseer_id: number;
  audio_url: string;
  duration: number;
  file_size: number;
  format: string;
  cloudinary_public_id?: string;
  tafseer?: ITafseer;
  created_at: string;
  updated_at: string;
}

export interface IAudioTafseerCreateData {
  tafseer_id: number;
  audio_url: string;
  duration: number;
  file_size: number;
  format: string;
  cloudinary_public_id?: string;
}
```

### Cloudinary Interfaces

```typescript
export interface ICloudinaryUploadResponse {
  public_id: string;
  version: number;
  signature: string;
  width?: number;
  height?: number;
  format: string;
  resource_type: string;
  created_at: string;
  tags: string[];
  bytes: number;
  type: string;
  etag: string;
  placeholder: boolean;
  url: string;
  secure_url: string;
  access_mode: string;
  original_filename: string;
  duration?: number;
  bit_rate?: number;
  frame_rate?: number;
}

export interface ICloudinaryAudioQualities {
  original: string;
  high: string;
  medium: string;
  low: string;
}

export interface ICloudinaryStats {
  plan: string;
  last_updated: string;
  objects: {
    usage: number;
  };
  bandwidth: {
    usage: number;
    limit: number;
  };
  storage: {
    usage: number;
    limit: number;
  };
  requests: {
    usage: number;
    limit: number;
  };
  transformations: {
    usage: number;
    limit: number;
  };
}
```

### Pagination Interfaces

```typescript
export interface IPaginationParams {
  page?: number;
  per_page?: number;
}

export interface IPaginationMeta {
  per_page: number;
  current_page: number;
  next_page: number | null;
  total_pages: number;
  total_records: number;
}

export interface IPaginatedResponse<T> {
  data: T[];
  pagination: IPaginationMeta;
}

// Specific paginated responses
export interface IPaginatedAudioFilesResponse {
  audio_files: IAudioFile[];
  pagination: IPaginationMeta;
}
```

---

## Service Layer Implementation

### Base API Service

**File:** `src/services/api.ts`

```typescript
import axios, { AxiosInstance, AxiosRequestConfig } from 'axios';

const API_BASE_URL = 'http://localhost/QuranAudio';

class ApiService {
  private axiosInstance: AxiosInstance;

  constructor() {
    this.axiosInstance = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Content-Type': 'application/json',
      },
    });

    // Request interceptor - Add auth token
    this.axiosInstance.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor - Handle token refresh
    this.axiosInstance.interceptors.response.use(
      (response) => response,
      async (error) => {
        const originalRequest = error.config;
        
        if (error.response?.status === 401 && !originalRequest._retry) {
          originalRequest._retry = true;
          
          // Clear token and redirect to login
          localStorage.removeItem('auth_token');
          window.location.href = '/login';
          return Promise.reject(error);
        }
        
        return Promise.reject(error);
      }
    );
  }

  public async get<T>(url: string, config?: AxiosRequestConfig) {
    return this.axiosInstance.get<T>(url, config);
  }

  public async post<T>(url: string, data?: unknown, config?: AxiosRequestConfig) {
    return this.axiosInstance.post<T>(url, data, config);
  }

  public async put<T>(url: string, data?: unknown, config?: AxiosRequestConfig) {
    return this.axiosInstance.put<T>(url, data, config);
  }

  public async delete<T>(url: string, config?: AxiosRequestConfig) {
    return this.axiosInstance.delete<T>(url, config);
  }
}

export default new ApiService();
```

### Authentication Service

**File:** `src/services/AuthService.ts`

```typescript
import apiService from './api';
import type { IUser, ILoginCredentials, ILoginResponse, IRegisterData } from 'src/interfaces/IAuth';

class AuthServiceClass {
  private baseUrl = '/auth';

  public async login(credentials: ILoginCredentials): Promise<ILoginResponse> {
    const response = await apiService.post<ILoginResponse>(`${this.baseUrl}/login`, credentials);
    return response.data;
  }

  public async register(data: IRegisterData): Promise<IUser> {
    const response = await apiService.post<{ user: IUser }>(`${this.baseUrl}/register`, data);
    return response.data.user;
  }

  public async testProtected(): Promise<{ message: string; user: IUser }> {
    const response = await apiService.get<{ message: string; user: IUser }>(`${this.baseUrl}/test-protected`);
    return response.data;
  }

  public async testAdmin(): Promise<{ message: string; user: IUser }> {
    const response = await apiService.get<{ message: string; user: IUser }>(`${this.baseUrl}/test-admin`);
    return response.data;
  }

  public async testSuperadmin(): Promise<{ message: string; user: IUser }> {
    const response = await apiService.get<{ message: string; user: IUser }>(`${this.baseUrl}/test-superadmin`);
    return response.data;
  }
}

export const AuthService = new AuthServiceClass();
```
### Reciter Service

**File:** `src/services/ReciterService.ts`

```typescript
import apiService from './api';
import type { IReciter, IReciterCreateData } from 'src/interfaces/IReciter';

class ReciterServiceClass {
  private baseUrl = '/chapter-reciters';

  public async getAllReciters(language?: string): Promise<IReciter[]> {
    const params = language ? { language } : {};
    const response = await apiService.get<{ reciters: IReciter[] }>(this.baseUrl, { params });
    return response.data.reciters;
  }

  public async getReciterById(id: number): Promise<IReciter> {
    const response = await apiService.get<{ reciter: IReciter }>(`${this.baseUrl}/${id}`);
    return response.data.reciter;
  }

  public async createReciter(data: IReciterCreateData): Promise<IReciter> {
    const response = await apiService.post<{ reciter: IReciter }>(this.baseUrl, data);
    return response.data.reciter;
  }

  public async updateReciter(id: number, data: Partial<IReciterCreateData>): Promise<IReciter> {
    const response = await apiService.put<{ reciter: IReciter }>(`${this.baseUrl}/${id}`, data);
    return response.data.reciter;
  }

  public async deleteReciter(id: number): Promise<void> {
    await apiService.delete(`${this.baseUrl}/${id}`);
  }
}

export const ReciterService = new ReciterServiceClass();
```

### Audio Service

**File:** `src/services/AudioService.ts`

```typescript
import apiService from './api';
import type { 
  IAudioFile, 
  IAudioFileCreateData, 
  IPaginationParams, 
  IPaginatedAudioFilesResponse 
} from 'src/interfaces/IAudio';

interface IAudioFileParams extends IPaginationParams {
  chapter_number?: number;
  juz_number?: number;
  page_number?: number;
  hizb_number?: number;
  rub_el_hizb_number?: number;
  fields?: string;
  segments?: boolean;
  language?: string;
}

class AudioServiceClass {
  private baseUrl = '/audio-files';

  // Single audio file operations
  public async getAudioFileById(id: number): Promise<IAudioFile> {
    const response = await apiService.get<{ audio_file: IAudioFile }>(`${this.baseUrl}/${id}`);
    return response.data.audio_file;
  }

  public async createAudioFile(data: IAudioFileCreateData): Promise<IAudioFile> {
    const response = await apiService.post<{ audio_file: IAudioFile }>(this.baseUrl, data);
    return response.data.audio_file;
  }

  public async updateAudioFile(id: number, data: Partial<IAudioFileCreateData>): Promise<IAudioFile> {
    const response = await apiService.put<{ audio_file: IAudioFile }>(`${this.baseUrl}/${id}`, data);
    return response.data.audio_file;
  }

  public async deleteAudioFile(id: number): Promise<void> {
    await apiService.delete(`${this.baseUrl}/${id}`);
  }

  // Reciter audio operations
  public async getChapterAudio(reciterId: number, chapterNumber: number, segments?: boolean): Promise<IAudioFile> {
    const params = segments ? { segments } : {};
    const response = await apiService.get<{ audio_file: IAudioFile }>(
      `/reciters/${reciterId}/chapters/${chapterNumber}`, 
      { params }
    );
    return response.data.audio_file;
  }

  public async getReciterAudioFiles(reciterId: number, params?: IAudioFileParams): Promise<IAudioFile[]> {
    const response = await apiService.get<{ audio_files: IAudioFile[] }>(
      `/reciters/${reciterId}/audio-files`, 
      { params }
    );
    return response.data.audio_files;
  }

  // Recitation audio operations
  public async getRecitationAudioFiles(recitationId: number, params?: IAudioFileParams): Promise<IAudioFile[]> {
    const response = await apiService.get<{ audio_files: IAudioFile[] }>(
      `/recitation-audio-files/${recitationId}`, 
      { params }
    );
    return response.data.audio_files;
  }

  // Resource recitation operations (with pagination)
  public async getSurahAyahRecitations(
    recitationId: number, 
    chapterNumber: number, 
    params?: IPaginationParams
  ): Promise<IPaginatedAudioFilesResponse> {
    const response = await apiService.get<IPaginatedAudioFilesResponse>(
      `/resources/recitations/${recitationId}/${chapterNumber}`, 
      { params }
    );
    return response.data;
  }

  public async getJuzAyahRecitations(
    recitationId: number, 
    juzNumber: number, 
    params?: IPaginationParams
  ): Promise<IPaginatedAudioFilesResponse> {
    const response = await apiService.get<IPaginatedAudioFilesResponse>(
      `/resources/recitations/${recitationId}/juz/${juzNumber}`, 
      { params }
    );
    return response.data;
  }

  public async getPageAyahRecitations(
    recitationId: number, 
    pageNumber: number, 
    params?: IPaginationParams
  ): Promise<IPaginatedAudioFilesResponse> {
    const response = await apiService.get<IPaginatedAudioFilesResponse>(
      `/resources/recitations/${recitationId}/pages/${pageNumber}`, 
      { params }
    );
    return response.data;
  }

  public async getRubElHizbAyahRecitations(
    recitationId: number, 
    rubElHizbNumber: number, 
    params?: IPaginationParams
  ): Promise<IPaginatedAudioFilesResponse> {
    const response = await apiService.get<IPaginatedAudioFilesResponse>(
      `/resources/recitations/${recitationId}/rub-el-hizb/${rubElHizbNumber}`, 
      { params }
    );
    return response.data;
  }

  public async getHizbAyahRecitations(
    recitationId: number, 
    hizbNumber: number, 
    params?: IPaginationParams
  ): Promise<IPaginatedAudioFilesResponse> {
    const response = await apiService.get<IPaginatedAudioFilesResponse>(
      `/resources/recitations/${recitationId}/hizb/${hizbNumber}`, 
      { params }
    );
    return response.data;
  }

  public async getAyahRecitation(recitationId: number, ayahKey: string): Promise<IAudioFile> {
    const response = await apiService.get<{ audio_file: IAudioFile }>(
      `/resources/ayah-recitation/${recitationId}/${ayahKey}`
    );
    return response.data.audio_file;
  }
}

export const AudioService = new AudioServiceClass();
```
### Tafseer Service

**File:** `src/services/TafseerService.ts`

```typescript
import apiService from './api';
import type { ITafseer, ITafseerCreateData, IMufasser, IMufasserCreateData } from 'src/interfaces/ITafseer';

class TafseerServiceClass {
  private baseUrl = '/tafseers';
  private mufasserBaseUrl = '/mufassers';

  // Tafseer operations
  public async getAllTafseers(): Promise<ITafseer[]> {
    const response = await apiService.get<{ tafseers: ITafseer[] }>(this.baseUrl);
    return response.data.tafseers;
  }

  public async getTafseerById(id: number): Promise<ITafseer> {
    const response = await apiService.get<{ tafseer: ITafseer }>(`${this.baseUrl}/${id}`);
    return response.data.tafseer;
  }

  public async getTafseerAudioFiles(id: number): Promise<any[]> {
    const response = await apiService.get<{ audio_files: any[] }>(`${this.baseUrl}/${id}/audio-files`);
    return response.data.audio_files;
  }

  public async getTafseerByVerseRange(verseFrom: string, verseTo?: string): Promise<ITafseer[]> {
    const url = verseTo 
      ? `${this.baseUrl}/verses/${verseFrom}/${verseTo}`
      : `${this.baseUrl}/verses/${verseFrom}`;
    const response = await apiService.get<{ tafseers: ITafseer[] }>(url);
    return response.data.tafseers;
  }

  public async createTafseer(data: ITafseerCreateData): Promise<ITafseer> {
    const response = await apiService.post<{ tafseer: ITafseer }>(this.baseUrl, data);
    return response.data.tafseer;
  }

  public async updateTafseer(id: number, data: Partial<ITafseerCreateData>): Promise<ITafseer> {
    const response = await apiService.put<{ tafseer: ITafseer }>(`${this.baseUrl}/${id}`, data);
    return response.data.tafseer;
  }

  public async deleteTafseer(id: number): Promise<void> {
    await apiService.delete(`${this.baseUrl}/${id}`);
  }

  // Mufasser operations
  public async getAllMufassers(): Promise<IMufasser[]> {
    const response = await apiService.get<{ mufassers: IMufasser[] }>(this.mufasserBaseUrl);
    return response.data.mufassers;
  }

  public async getMufasserById(id: number): Promise<IMufasser> {
    const response = await apiService.get<{ mufasser: IMufasser }>(`${this.mufasserBaseUrl}/${id}`);
    return response.data.mufasser;
  }

  public async getMufasserTafseers(id: number): Promise<ITafseer[]> {
    const response = await apiService.get<{ tafseers: ITafseer[] }>(`${this.mufasserBaseUrl}/${id}/tafseers`);
    return response.data.tafseers;
  }

  public async createMufasser(data: IMufasserCreateData): Promise<IMufasser> {
    const response = await apiService.post<{ mufasser: IMufasser }>(this.mufasserBaseUrl, data);
    return response.data.mufasser;
  }

  public async updateMufasser(id: number, data: Partial<IMufasserCreateData>): Promise<IMufasser> {
    const response = await apiService.put<{ mufasser: IMufasser }>(`${this.mufasserBaseUrl}/${id}`, data);
    return response.data.mufasser;
  }

  public async deleteMufasser(id: number): Promise<void> {
    await apiService.delete(`${this.mufasserBaseUrl}/${id}`);
  }
}

export const TafseerService = new TafseerServiceClass();
```

### Cloudinary Service

**File:** `src/services/CloudinaryService.ts`

```typescript
import apiService from './api';
import type { 
  ICloudinaryUploadResponse, 
  ICloudinaryAudioQualities, 
  ICloudinaryStats,
  IAudioTafseer,
  IAudioTafseerCreateData
} from 'src/interfaces/ICloudinary';

interface IUploadTafseerAudioData {
  tafseer_id: number;
  audio_file?: File;
  audio_url?: string;
  title?: string;
  description?: string;
}

interface IBatchUploadData {
  uploads: IUploadTafseerAudioData[];
}

class CloudinaryServiceClass {
  private baseUrl = '/cloudinary';

  public async testConnection(): Promise<{ status: string; message: string }> {
    const response = await apiService.get<{ status: string; message: string }>(`${this.baseUrl}/test`);
    return response.data;
  }

  public async getUsageStats(): Promise<ICloudinaryStats> {
    const response = await apiService.get<ICloudinaryStats>(`${this.baseUrl}/stats`);
    return response.data;
  }

  public async uploadTafseerAudio(data: IUploadTafseerAudioData): Promise<IAudioTafseer> {
    const formData = new FormData();
    
    formData.append('tafseer_id', data.tafseer_id.toString());
    
    if (data.audio_file) {
      formData.append('audio_file', data.audio_file);
    }
    
    if (data.audio_url) {
      formData.append('audio_url', data.audio_url);
    }
    
    if (data.title) {
      formData.append('title', data.title);
    }
    
    if (data.description) {
      formData.append('description', data.description);
    }

    const response = await apiService.post<{ audio_tafseer: IAudioTafseer }>(
      `${this.baseUrl}/tafseer-audio`, 
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );
    return response.data.audio_tafseer;
  }

  public async getTafseerAudioWithQualities(id: number): Promise<{
    audio_tafseer: IAudioTafseer;
    qualities: ICloudinaryAudioQualities;
  }> {
    const response = await apiService.get<{
      audio_tafseer: IAudioTafseer;
      qualities: ICloudinaryAudioQualities;
    }>(`${this.baseUrl}/tafseer-audio/${id}`);
    return response.data;
  }

  public async updateTafseerAudio(id: number, data: Partial<IAudioTafseerCreateData>): Promise<IAudioTafseer> {
    const response = await apiService.put<{ audio_tafseer: IAudioTafseer }>(
      `${this.baseUrl}/tafseer-audio/${id}`, 
      data
    );
    return response.data.audio_tafseer;
  }

  public async deleteTafseerAudio(id: number): Promise<{ message: string }> {
    const response = await apiService.delete<{ message: string }>(`${this.baseUrl}/tafseer-audio/${id}`);
    return response.data;
  }

  public async batchUploadTafseerAudio(data: IBatchUploadData): Promise<{
    successful: IAudioTafseer[];
    failed: Array<{ error: string; data: IUploadTafseerAudioData }>;
  }> {
    const response = await apiService.post<{
      successful: IAudioTafseer[];
      failed: Array<{ error: string; data: IUploadTafseerAudioData }>;
    }>(`${this.baseUrl}/tafseer-audio/batch`, data);
    return response.data;
  }

  public async migrateTafseerToCloudinary(id: number): Promise<IAudioTafseer> {
    const response = await apiService.post<{ audio_tafseer: IAudioTafseer }>(
      `${this.baseUrl}/tafseer-audio/${id}/migrate`
    );
    return response.data.audio_tafseer;
  }

  public async getAudioInfo(publicId: string): Promise<ICloudinaryUploadResponse> {
    const response = await apiService.get<ICloudinaryUploadResponse>(
      `${this.baseUrl}/audio-info/${encodeURIComponent(publicId)}`
    );
    return response.data;
  }
}

export const CloudinaryService = new CloudinaryServiceClass();
```
---

## Composables Layer

### Authentication Composable

**File:** `src/composables/useAuth.ts`

```typescript
import { ref, computed, readonly } from 'vue';
import { AuthService } from 'src/services/AuthService';
import type { IUser, ILoginCredentials, IRegisterData } from 'src/interfaces/IAuth';

const currentUser = ref<IUser | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

export const useAuth = () => {
  const isAuthenticated = computed(() => !!currentUser.value);
  const isAdmin = computed(() => 
    currentUser.value?.role_slug === 'admin' || currentUser.value?.role_slug === 'superadmin'
  );
  const isSuperadmin = computed(() => currentUser.value?.role_slug === 'superadmin');

  const login = async (credentials: ILoginCredentials) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await AuthService.login(credentials);
      localStorage.setItem('auth_token', response.token);
      currentUser.value = response.user;
      return response;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Login failed';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const register = async (data: IRegisterData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const user = await AuthService.register(data);
      return user;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Registration failed';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const logout = () => {
    localStorage.removeItem('auth_token');
    currentUser.value = null;
    error.value = null;
  };

  const checkAuth = () => {
    const token = localStorage.getItem('auth_token');
    return !!token;
  };

  const testProtected = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await AuthService.testProtected();
      return response;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Protected route test failed';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const testAdmin = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await AuthService.testAdmin();
      return response;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Admin route test failed';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const testSuperadmin = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await AuthService.testSuperadmin();
      return response;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Superadmin route test failed';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  return {
    currentUser: readonly(currentUser),
    loading: readonly(loading),
    error: readonly(error),
    isAuthenticated,
    isAdmin,
    isSuperadmin,
    login,
    register,
    logout,
    checkAuth,
    testProtected,
    testAdmin,
    testSuperadmin,
  };
};
```

### Reciters Composable

**File:** `src/composables/useReciters.ts`

```typescript
import { ref, readonly } from 'vue';
import { ReciterService } from 'src/services/ReciterService';
import type { IReciter, IReciterCreateData } from 'src/interfaces/IReciter';

const reciters = ref<IReciter[]>([]);
const currentReciter = ref<IReciter | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

export const useReciters = () => {
  const fetchReciters = async (language?: string) => {
    loading.value = true;
    error.value = null;
    
    try {
      reciters.value = await ReciterService.getAllReciters(language);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch reciters';
      console.error('Error fetching reciters:', e);
    } finally {
      loading.value = false;
    }
  };

  const fetchReciterById = async (id: number) => {
    loading.value = true;
    error.value = null;
    
    try {
      currentReciter.value = await ReciterService.getReciterById(id);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch reciter';
      console.error('Error fetching reciter:', e);
    } finally {
      loading.value = false;
    }
  };

  const createReciter = async (data: IReciterCreateData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const newReciter = await ReciterService.createReciter(data);
      reciters.value.push(newReciter);
      return newReciter;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to create reciter';
      console.error('Error creating reciter:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const updateReciter = async (id: number, data: Partial<IReciterCreateData>) => {
    loading.value = true;
    error.value = null;
    
    try {
      const updatedReciter = await ReciterService.updateReciter(id, data);
      const index = reciters.value.findIndex(r => r.id === id);
      if (index !== -1) {
        reciters.value[index] = updatedReciter;
      }
      if (currentReciter.value?.id === id) {
        currentReciter.value = updatedReciter;
      }
      return updatedReciter;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to update reciter';
      console.error('Error updating reciter:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const deleteReciter = async (id: number) => {
    loading.value = true;
    error.value = null;
    
    try {
      await ReciterService.deleteReciter(id);
      reciters.value = reciters.value.filter(r => r.id !== id);
      if (currentReciter.value?.id === id) {
        currentReciter.value = null;
      }
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to delete reciter';
      console.error('Error deleting reciter:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  };

  return {
    reciters: readonly(reciters),
    currentReciter: readonly(currentReciter),
    loading: readonly(loading),
    error: readonly(error),
    fetchReciters,
    fetchReciterById,
    createReciter,
    updateReciter,
    deleteReciter,
  };
};
```
### Audio Composable

**File:** `src/composables/useAudio.ts`

```typescript
import { ref, computed, readonly } from 'vue';
import { AudioService } from 'src/services/AudioService';
import type { 
  IAudioFile, 
  IAudioFileCreateData, 
  IPaginationParams, 
  IPaginatedAudioFilesResponse,
  IPaginationMeta 
} from 'src/interfaces/IAudio';

const audioFiles = ref<IAudioFile[]>([]);
const currentAudioFile = ref<IAudioFile | null>(null);
const pagination = ref<IPaginationMeta>({
  per_page: 10,
  current_page: 1,
  next_page: null,
  total_pages: 0,
  total_records: 0,
});
const loading = ref(false);
const error = ref<string | null>(null);

export const useAudio = () => {
  // Single audio file operations
  const fetchAudioFileById = async (id: number) => {
    loading.value = true;
    error.value = null;
    
    try {
      currentAudioFile.value = await AudioService.getAudioFileById(id);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch audio file';
      console.error('Error fetching audio file:', e);
    } finally {
      loading.value = false;
    }
  };

  const createAudioFile = async (data: IAudioFileCreateData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const newAudioFile = await AudioService.createAudioFile(data);
      audioFiles.value.push(newAudioFile);
      return newAudioFile;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to create audio file';
      console.error('Error creating audio file:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const updateAudioFile = async (id: number, data: Partial<IAudioFileCreateData>) => {
    loading.value = true;
    error.value = null;
    
    try {
      const updatedAudioFile = await AudioService.updateAudioFile(id, data);
      const index = audioFiles.value.findIndex(a => a.id === id);
      if (index !== -1) {
        audioFiles.value[index] = updatedAudioFile;
      }
      return updatedAudioFile;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to update audio file';
      console.error('Error updating audio file:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const deleteAudioFile = async (id: number) => {
    loading.value = true;
    error.value = null;
    
    try {
      await AudioService.deleteAudioFile(id);
      audioFiles.value = audioFiles.value.filter(a => a.id !== id);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to delete audio file';
      console.error('Error deleting audio file:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  };

  // Reciter audio operations
  const fetchChapterAudio = async (reciterId: number, chapterNumber: number, segments?: boolean) => {
    loading.value = true;
    error.value = null;
    
    try {
      currentAudioFile.value = await AudioService.getChapterAudio(reciterId, chapterNumber, segments);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch chapter audio';
      console.error('Error fetching chapter audio:', e);
    } finally {
      loading.value = false;
    }
  };

  const fetchReciterAudioFiles = async (reciterId: number, params?: any) => {
    loading.value = true;
    error.value = null;
    
    try {
      audioFiles.value = await AudioService.getReciterAudioFiles(reciterId, params);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch reciter audio files';
      console.error('Error fetching reciter audio files:', e);
    } finally {
      loading.value = false;
    }
  };

  // Paginated resource recitation operations
  const fetchSurahAyahRecitations = async (
    recitationId: number, 
    chapterNumber: number, 
    params?: IPaginationParams
  ) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await AudioService.getSurahAyahRecitations(recitationId, chapterNumber, params);
      audioFiles.value = response.audio_files;
      pagination.value = response.pagination;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch surah ayah recitations';
      console.error('Error fetching surah ayah recitations:', e);
    } finally {
      loading.value = false;
    }
  };

  const fetchJuzAyahRecitations = async (
    recitationId: number, 
    juzNumber: number, 
    params?: IPaginationParams
  ) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await AudioService.getJuzAyahRecitations(recitationId, juzNumber, params);
      audioFiles.value = response.audio_files;
      pagination.value = response.pagination;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch juz ayah recitations';
      console.error('Error fetching juz ayah recitations:', e);
    } finally {
      loading.value = false;
    }
  };

  const fetchPageAyahRecitations = async (
    recitationId: number, 
    pageNumber: number, 
    params?: IPaginationParams
  ) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await AudioService.getPageAyahRecitations(recitationId, pageNumber, params);
      audioFiles.value = response.audio_files;
      pagination.value = response.pagination;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch page ayah recitations';
      console.error('Error fetching page ayah recitations:', e);
    } finally {
      loading.value = false;
    }
  };

  const fetchAyahRecitation = async (recitationId: number, ayahKey: string) => {
    loading.value = true;
    error.value = null;
    
    try {
      currentAudioFile.value = await AudioService.getAyahRecitation(recitationId, ayahKey);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch ayah recitation';
      console.error('Error fetching ayah recitation:', e);
    } finally {
      loading.value = false;
    }
  };

  // Pagination helpers
  const goToPage = async (
    page: number, 
    fetchFunction: (params?: IPaginationParams) => Promise<void>
  ) => {
    await fetchFunction({ page, per_page: pagination.value.per_page });
  };

  const changePageSize = async (
    perPage: number, 
    fetchFunction: (params?: IPaginationParams) => Promise<void>
  ) => {
    await fetchFunction({ page: 1, per_page: perPage });
  };

  const nextPage = async (fetchFunction: (params?: IPaginationParams) => Promise<void>) => {
    if (pagination.value.next_page) {
      await goToPage(pagination.value.next_page, fetchFunction);
    }
  };

  const prevPage = async (fetchFunction: (params?: IPaginationParams) => Promise<void>) => {
    if (pagination.value.current_page > 1) {
      await goToPage(pagination.value.current_page - 1, fetchFunction);
    }
  };

  // Computed properties
  const hasNextPage = computed(() => !!pagination.value.next_page);
  const hasPrevPage = computed(() => pagination.value.current_page > 1);
  const totalPages = computed(() => pagination.value.total_pages);
  const currentPage = computed(() => pagination.value.current_page);

  return {
    audioFiles: readonly(audioFiles),
    currentAudioFile: readonly(currentAudioFile),
    pagination: readonly(pagination),
    loading: readonly(loading),
    error: readonly(error),
    hasNextPage,
    hasPrevPage,
    totalPages,
    currentPage,
    fetchAudioFileById,
    createAudioFile,
    updateAudioFile,
    deleteAudioFile,
    fetchChapterAudio,
    fetchReciterAudioFiles,
    fetchSurahAyahRecitations,
    fetchJuzAyahRecitations,
    fetchPageAyahRecitations,
    fetchAyahRecitation,
    goToPage,
    changePageSize,
    nextPage,
    prevPage,
  };
};
```
### Tafseer Composable

**File:** `src/composables/useTafseer.ts`

```typescript
import { ref, readonly } from 'vue';
import { TafseerService } from 'src/services/TafseerService';
import type { ITafseer, ITafseerCreateData, IMufasser, IMufasserCreateData } from 'src/interfaces/ITafseer';

const tafseers = ref<ITafseer[]>([]);
const currentTafseer = ref<ITafseer | null>(null);
const mufassers = ref<IMufasser[]>([]);
const currentMufasser = ref<IMufasser | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

export const useTafseer = () => {
  // Tafseer operations
  const fetchTafseers = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      tafseers.value = await TafseerService.getAllTafseers();
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch tafseers';
      console.error('Error fetching tafseers:', e);
    } finally {
      loading.value = false;
    }
  };

  const fetchTafseerById = async (id: number) => {
    loading.value = true;
    error.value = null;
    
    try {
      currentTafseer.value = await TafseerService.getTafseerById(id);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch tafseer';
      console.error('Error fetching tafseer:', e);
    } finally {
      loading.value = false;
    }
  };

  const fetchTafseerByVerseRange = async (verseFrom: string, verseTo?: string) => {
    loading.value = true;
    error.value = null;
    
    try {
      tafseers.value = await TafseerService.getTafseerByVerseRange(verseFrom, verseTo);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch tafseer by verse range';
      console.error('Error fetching tafseer by verse range:', e);
    } finally {
      loading.value = false;
    }
  };

  const createTafseer = async (data: ITafseerCreateData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const newTafseer = await TafseerService.createTafseer(data);
      tafseers.value.push(newTafseer);
      return newTafseer;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to create tafseer';
      console.error('Error creating tafseer:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const updateTafseer = async (id: number, data: Partial<ITafseerCreateData>) => {
    loading.value = true;
    error.value = null;
    
    try {
      const updatedTafseer = await TafseerService.updateTafseer(id, data);
      const index = tafseers.value.findIndex(t => t.id === id);
      if (index !== -1) {
        tafseers.value[index] = updatedTafseer;
      }
      return updatedTafseer;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to update tafseer';
      console.error('Error updating tafseer:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const deleteTafseer = async (id: number) => {
    loading.value = true;
    error.value = null;
    
    try {
      await TafseerService.deleteTafseer(id);
      tafseers.value = tafseers.value.filter(t => t.id !== id);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to delete tafseer';
      console.error('Error deleting tafseer:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  };

  // Mufasser operations
  const fetchMufassers = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      mufassers.value = await TafseerService.getAllMufassers();
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch mufassers';
      console.error('Error fetching mufassers:', e);
    } finally {
      loading.value = false;
    }
  };

  const fetchMufasserById = async (id: number) => {
    loading.value = true;
    error.value = null;
    
    try {
      currentMufasser.value = await TafseerService.getMufasserById(id);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch mufasser';
      console.error('Error fetching mufasser:', e);
    } finally {
      loading.value = false;
    }
  };

  const fetchMufasserTafseers = async (id: number) => {
    loading.value = true;
    error.value = null;
    
    try {
      tafseers.value = await TafseerService.getMufasserTafseers(id);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch mufasser tafseers';
      console.error('Error fetching mufasser tafseers:', e);
    } finally {
      loading.value = false;
    }
  };

  const createMufasser = async (data: IMufasserCreateData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const newMufasser = await TafseerService.createMufasser(data);
      mufassers.value.push(newMufasser);
      return newMufasser;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to create mufasser';
      console.error('Error creating mufasser:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const updateMufasser = async (id: number, data: Partial<IMufasserCreateData>) => {
    loading.value = true;
    error.value = null;
    
    try {
      const updatedMufasser = await TafseerService.updateMufasser(id, data);
      const index = mufassers.value.findIndex(m => m.id === id);
      if (index !== -1) {
        mufassers.value[index] = updatedMufasser;
      }
      return updatedMufasser;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to update mufasser';
      console.error('Error updating mufasser:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const deleteMufasser = async (id: number) => {
    loading.value = true;
    error.value = null;
    
    try {
      await TafseerService.deleteMufasser(id);
      mufassers.value = mufassers.value.filter(m => m.id !== id);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to delete mufasser';
      console.error('Error deleting mufasser:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  };

  return {
    tafseers: readonly(tafseers),
    currentTafseer: readonly(currentTafseer),
    mufassers: readonly(mufassers),
    currentMufasser: readonly(currentMufasser),
    loading: readonly(loading),
    error: readonly(error),
    fetchTafseers,
    fetchTafseerById,
    fetchTafseerByVerseRange,
    createTafseer,
    updateTafseer,
    deleteTafseer,
    fetchMufassers,
    fetchMufasserById,
    fetchMufasserTafseers,
    createMufasser,
    updateMufasser,
    deleteMufasser,
  };
};
```
---

## Component Logic Patterns

### Page/Component Setup

**File:** `src/pages/RecitersPage.vue`

```typescript
import { ref, computed, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import { useReciters } from 'src/composables/useReciters';
import type { IReciter, IReciterCreateData } from 'src/interfaces/IReciter';

const $q = useQuasar();
const { 
  reciters, 
  currentReciter, 
  loading, 
  error, 
  fetchReciters, 
  fetchReciterById,
  createReciter, 
  updateReciter, 
  deleteReciter 
} = useReciters();

// Local component state
const showCreateDialog = ref(false);
const editMode = ref(false);
const selectedLanguage = ref('en');
const formData = ref<Partial<IReciterCreateData>>({
  name: '',
  arabic_name: '',
  relative_path: '',
  format: 'mp3',
  files_size: 0,
});

// CRUD Operations
const handleCreate = async () => {
  try {
    await createReciter(formData.value as IReciterCreateData);
    $q.notify({ type: 'positive', message: 'Reciter created successfully' });
    showCreateDialog.value = false;
    resetForm();
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Failed to create reciter' });
  }
};

const handleUpdate = async (id: number) => {
  try {
    await updateReciter(id, formData.value);
    $q.notify({ type: 'positive', message: 'Reciter updated successfully' });
    showCreateDialog.value = false;
    resetForm();
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Failed to update reciter' });
  }
};

const handleDelete = async (id: number) => {
  $q.dialog({
    title: 'Confirm Delete',
    message: 'Are you sure you want to delete this reciter?',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await deleteReciter(id);
      $q.notify({ type: 'positive', message: 'Reciter deleted successfully' });
    } catch (e) {
      $q.notify({ type: 'negative', message: 'Failed to delete reciter' });
    }
  });
};

const resetForm = () => {
  formData.value = {
    name: '',
    arabic_name: '',
    relative_path: '',
    format: 'mp3',
    files_size: 0,
  };
  editMode.value = false;
};

// Lifecycle
onMounted(() => {
  fetchReciters(selectedLanguage.value);
});
```

### Audio Player Component Logic

**File:** `src/components/AudioPlayer.vue`

```typescript
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useAudio } from 'src/composables/useAudio';
import type { IAudioFile, ITimestamp } from 'src/interfaces/IAudio';

interface Props {
  audioFile: IAudioFile | null;
  autoplay?: boolean;
  showTimestamps?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  autoplay: false,
  showTimestamps: true,
});

const audioElement = ref<HTMLAudioElement | null>(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const volume = ref(1);
const playbackRate = ref(1);
const currentSegmentIndex = ref(-1);

// Computed properties
const progress = computed(() => {
  return duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0;
});

const formattedCurrentTime = computed(() => {
  return formatTime(currentTime.value);
});

const formattedDuration = computed(() => {
  return formatTime(duration.value);
});

const currentTimestamp = computed(() => {
  if (!props.audioFile?.timestamps) return null;
  
  return props.audioFile.timestamps.find(timestamp => {
    const from = timestamp.timestamp_from / 1000; // Convert to seconds
    const to = timestamp.timestamp_to / 1000;
    return currentTime.value >= from && currentTime.value <= to;
  });
});

// Audio controls
const play = () => {
  if (audioElement.value) {
    audioElement.value.play();
    isPlaying.value = true;
  }
};

const pause = () => {
  if (audioElement.value) {
    audioElement.value.pause();
    isPlaying.value = false;
  }
};

const togglePlay = () => {
  if (isPlaying.value) {
    pause();
  } else {
    play();
  }
};

const seek = (time: number) => {
  if (audioElement.value) {
    audioElement.value.currentTime = time;
    currentTime.value = time;
  }
};

const seekToTimestamp = (timestamp: ITimestamp) => {
  const seekTime = timestamp.timestamp_from / 1000; // Convert to seconds
  seek(seekTime);
};

const setVolume = (newVolume: number) => {
  volume.value = Math.max(0, Math.min(1, newVolume));
  if (audioElement.value) {
    audioElement.value.volume = volume.value;
  }
};

const setPlaybackRate = (rate: number) => {
  playbackRate.value = rate;
  if (audioElement.value) {
    audioElement.value.playbackRate = rate;
  }
};

// Utility functions
const formatTime = (seconds: number): string => {
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins}:${secs.toString().padStart(2, '0')}`;
};

// Audio event handlers
const handleLoadedMetadata = () => {
  if (audioElement.value) {
    duration.value = audioElement.value.duration;
  }
};

const handleTimeUpdate = () => {
  if (audioElement.value) {
    currentTime.value = audioElement.value.currentTime;
  }
};

const handleEnded = () => {
  isPlaying.value = false;
  currentTime.value = 0;
};

// Watch for audio file changes
watch(() => props.audioFile, (newAudioFile) => {
  if (newAudioFile && audioElement.value) {
    audioElement.value.src = newAudioFile.audio_url;
    if (props.autoplay) {
      play();
    }
  }
});

// Lifecycle
onMounted(() => {
  if (audioElement.value) {
    audioElement.value.addEventListener('loadedmetadata', handleLoadedMetadata);
    audioElement.value.addEventListener('timeupdate', handleTimeUpdate);
    audioElement.value.addEventListener('ended', handleEnded);
  }
});

onUnmounted(() => {
  if (audioElement.value) {
    audioElement.value.removeEventListener('loadedmetadata', handleLoadedMetadata);
    audioElement.value.removeEventListener('timeupdate', handleTimeUpdate);
    audioElement.value.removeEventListener('ended', handleEnded);
  }
});
```
---

## Error Handling Patterns

### Service Layer Error Handling

```typescript
import axios from 'axios';

public async getReciters(): Promise<IReciter[]> {
  try {
    const response = await apiService.get<{ reciters: IReciter[] }>(this.baseUrl);
    return response.data.reciters;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      if (error.response?.status === 404) {
        throw new Error('Reciters not found');
      } else if (error.response?.status === 401) {
        throw new Error('Unauthorized access');
      } else if (error.response?.status === 500) {
        throw new Error('Server error occurred');
      }
    }
    throw new Error('Failed to fetch reciters');
  }
}
```

### Composable Error Handling

```typescript
const fetchReciters = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    reciters.value = await ReciterService.getAllReciters();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'An error occurred';
    console.error('Error fetching reciters:', e);
  } finally {
    loading.value = false;
  }
};
```

### Component Error Display Logic

```typescript
import { computed } from 'vue';

const hasError = computed(() => !!error.value);
const isLoading = computed(() => loading.value);
const hasData = computed(() => reciters.value.length > 0);

const displayState = computed(() => {
  if (isLoading.value) return 'loading';
  if (hasError.value) return 'error';
  if (!hasData.value) return 'empty';
  return 'data';
});
```

---

## Authentication Flow

### Login Logic

```typescript
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuth } from 'src/composables/useAuth';

const router = useRouter();
const $q = useQuasar();
const { login, loading } = useAuth();

const credentials = ref({
  username: '',
  password: '',
});

const handleLogin = async () => {
  try {
    await login(credentials.value);
    $q.notify({
      type: 'positive',
      message: 'Login successful',
    });
    router.push('/dashboard');
  } catch (e) {
    $q.notify({
      type: 'negative',
      message: e instanceof Error ? e.message : 'Login failed',
    });
  }
};
```

---

## API Response Formats

### Success Response

```json
{
  "reciter": {
    "id": 1,
    "name": "Mishary Rashid Alafasy",
    "arabic_name": "مشاری راشد العفاسی",
    "relative_path": "/alafasy/",
    "format": "mp3",
    "files_size": 1024000
  }
}
```

### List Response

```json
{
  "reciters": [
    {
      "id": 1,
      "name": "Mishary Rashid Alafasy",
      "arabic_name": "مشاری راشد العفاسی"
    }
  ]
}
```

### Paginated Response

```json
{
  "audio_files": [
    {
      "id": 1001,
      "chapter_id": 1,
      "audio_url": "https://example.com/audio.mp3",
      "duration": 45000
    }
  ],
  "pagination": {
    "per_page": 10,
    "current_page": 1,
    "next_page": 2,
    "total_pages": 5,
    "total_records": 50
  }
}
```

### Error Response

```json
{
  "error": "Not Found",
  "message": "Resource not found"
}
```

---

## Best Practices

### 1. Always Use Composables

❌ **Wrong:**
```typescript
import { ReciterService } from 'src/services/ReciterService';

const reciters = ref([]);
onMounted(async () => {
  reciters.value = await ReciterService.getAllReciters(); // Direct service call
});
```

✅ **Correct:**
```typescript
import { useReciters } from 'src/composables/useReciters';

const { reciters, fetchReciters } = useReciters();
onMounted(() => {
  fetchReciters(); // Use composable
});
```

### 2. Handle Loading States

```typescript
const isLoading = computed(() => loading.value);
const canSubmit = computed(() => !loading.value && formIsValid.value);
```

### 3. Handle Errors Gracefully

```typescript
try {
  await createReciter(data);
  $q.notify({ type: 'positive', message: 'Success' });
} catch (e) {
  const message = e instanceof Error ? e.message : 'Operation failed';
  $q.notify({ type: 'negative', message });
}
```

### 4. Use TypeScript Strictly

```typescript
// Define interfaces
interface IFormData {
  name: string;
  arabic_name: string;
}

// Type your refs
const formData = ref<IFormData>({
  name: '',
  arabic_name: '',
});

// Type your functions
const handleSubmit = async (): Promise<void> => {
  // Implementation
};
```

### 5. Computed Properties for Derived State

```typescript
const filteredReciters = computed(() => 
  reciters.value.filter(r => r.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
);

const hasReciters = computed(() => reciters.value.length > 0);
```

---

## Testing with curl

Test backend endpoints before frontend integration:

```bash
# Health check
curl.exe -X GET http://localhost/QuranAudio/health

# Login
curl.exe -X POST http://localhost/QuranAudio/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"username\":\"admin\",\"password\":\"admin123\"}"

# Get reciters
curl.exe -X GET "http://localhost/QuranAudio/chapter-reciters?language=en"

# Get chapter audio with segments
curl.exe -X GET "http://localhost/QuranAudio/reciters/1/chapters/1?segments=true"

# Get paginated surah ayah recitations
curl.exe -X GET "http://localhost/QuranAudio/resources/recitations/1/1?page=1&per_page=10"

# Protected route test
curl.exe -X GET http://localhost/QuranAudio/auth/test-protected ^
  -H "Authorization: Bearer YOUR_TOKEN"

# Create reciter (protected)
curl.exe -X POST http://localhost/QuranAudio/chapter-reciters ^
  -H "Content-Type: application/json" ^
  -H "Authorization: Bearer YOUR_TOKEN" ^
  -d "{\"name\":\"New Reciter\",\"arabic_name\":\"قارئ جديد\",\"relative_path\":\"/new-reciter/\",\"format\":\"mp3\",\"files_size\":1024000}"

# Upload tafseer audio to Cloudinary
curl.exe -X POST http://localhost/QuranAudio/cloudinary/tafseer-audio ^
  -H "Authorization: Bearer YOUR_TOKEN" ^
  -F "tafseer_id=1" ^
  -F "audio_file=@path/to/audio.mp3" ^
  -F "title=Tafseer Audio Title"

# Test Cloudinary connection
curl.exe -X GET http://localhost/QuranAudio/cloudinary/test
```

---

## Summary

This guide provides:

1. **Complete API endpoint reference** with authentication and pagination
2. **TypeScript interfaces** for all data models (Reciter, Audio, Tafseer, Cloudinary)
3. **Service layer** implementation with base API service and error handling
4. **Composables** for reactive state management following Vue 3 patterns
5. **Component logic patterns** for pages and audio player functionality
6. **Error handling** at all architectural layers
7. **Authentication flow** with JWT tokens and role-based access
8. **Best practices** for clean Vue 3 + TypeScript architecture
9. **Testing commands** using curl for backend validation

Follow the layered architecture strictly: **Pages/Components → Composables → Services → API**

Focus on logic and data flow - UI implementation should use Quasar components as documented at https://quasar.dev

The API provides comprehensive Quran audio functionality including:
- Reciter and recitation management
- Audio file access by various Quranic structures (chapter, juz, page, hizb, rub el hizb, ayah)
- Tafseer (commentary) with audio support
- Cloudinary integration for audio storage and quality optimization
- Word-level timestamps for precise audio playback
- Server-side pagination for efficient data loading
- JWT authentication with role-based access control