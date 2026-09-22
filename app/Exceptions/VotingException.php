<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class VotingException extends Exception
{
    /**
     * Kode error untuk frontend.
     */
    protected string $errorCode;

    /**
     * HTTP status yang dipakai.
     */
    protected int $httpStatus;

    /**
     * Konteks tambahan (device_id, session_id, dll).
     */
    protected array $context;

    public function __construct(
        string $message = 'Terjadi kesalahan pada voting.',
        string $errorCode = 'voting.error',
        int $httpStatus = 422,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->errorCode  = $errorCode;
        $this->httpStatus = $httpStatus;
        $this->context    = $context;
    }

    // ==========================================
    // GETTERS
    // ==========================================

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    // ==========================================
    // RENDER
    // ==========================================

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        // Log semua error voting
        Log::warning('VotingException', [
            'code'    => $this->errorCode,
            'message' => $this->getMessage(),
            'context' => $this->context,
            'user_id' => auth()->id(),
            'ip'      => $request->ip(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'error',
                'code'    => $this->errorCode,
                'message' => $this->getMessage(),
                'context' => $this->context ?: null,
            ], $this->httpStatus);
        }

        return back()->withErrors([
            'voting' => $this->getMessage(),
        ]);
    }

    // ==========================================
    // STATIC FACTORY METHODS
    // ==========================================

    /**
     * Device sedang dipakai voter lain.
     */
    public static function deviceBusy(array $context = []): self
    {
        return new self(
            message: 'Device sedang dipakai. Tunggu sampai siswa sebelumnya selesai.',
            errorCode: 'device.busy',
            httpStatus: 409,
            context: $context,
        );
    }

    /**
     * Device tidak ditemukan / QR expired.
     */
    public static function deviceNotFound(array $context = []): self
    {
        return new self(
            message: 'Device tidak ditemukan atau QR sudah kadaluarsa.',
            errorCode: 'device.not_found',
            httpStatus: 404,
            context: $context,
        );
    }

    /**
     * Voter sudah memilih.
     */
    public static function alreadyVoted(array $context = []): self
    {
        return new self(
            message: 'Anda sudah menggunakan hak suara Anda.',
            errorCode: 'voter.already_voted',
            httpStatus: 409,
            context: $context,
        );
    }

    /**
     * Voter belum check-in.
     */
    public static function notCheckedIn(array $context = []): self
    {
        return new self(
            message: 'Silakan check-in dulu di pintu masuk.',
            errorCode: 'voter.not_checked_in',
            httpStatus: 422,
            context: $context,
        );
    }

    /**
     * Voter belum terdaftar di election.
     */
    public static function voterNotFound(array $context = []): self
    {
        return new self(
            message: 'Data pemilih Anda belum terdaftar. Hubungi panitia.',
            errorCode: 'voter.not_found',
            httpStatus: 404,
            context: $context,
        );
    }

    /**
     * Sesi kelas tidak aktif.
     */
    public static function sessionNotActive(array $context = []): self
    {
        return new self(
            message: 'Sesi voting kelas Anda sedang tidak aktif.',
            errorCode: 'session.not_active',
            httpStatus: 422,
            context: $context,
        );
    }

    /**
     * Sesi sudah berakhir.
     */
    public static function sessionEnded(array $context = []): self
    {
        return new self(
            message: 'Waktu sesi sudah berakhir.',
            errorCode: 'session.ended',
            httpStatus: 422,
            context: $context,
        );
    }

    /**
     * Election tidak aktif / belum dibuka.
     */
    public static function electionNotActive(array $context = []): self
    {
        return new self(
            message: 'Pemilihan belum atau tidak aktif.',
            errorCode: 'election.not_active',
            httpStatus: 422,
            context: $context,
        );
    }

    /**
     * Election sudah berakhir.
     */
    public static function electionEnded(array $context = []): self
    {
        return new self(
            message: 'Waktu pemilihan sudah berakhir.',
            errorCode: 'election.ended',
            httpStatus: 422,
            context: $context,
        );
    }

    /**
     * Kelas tidak sesuai dengan sesi.
     */
    public static function classMismatch(array $context = []): self
    {
        return new self(
            message: 'Anda bukan bagian dari kelas ini.',
            errorCode: 'voter.class_mismatch',
            httpStatus: 403,
            context: $context,
        );
    }

    /**
     * Kandidat tidak valid (bukan di election ini).
     */
    public static function candidateInvalid(array $context = []): self
    {
        return new self(
            message: 'Kandidat tidak valid untuk pemilihan ini.',
            errorCode: 'candidate.invalid',
            httpStatus: 422,
            context: $context,
        );
    }

    /**
     * Akses dari luar jaringan sekolah.
     */
    public static function outsideNetwork(array $context = []): self
    {
        return new self(
            message: 'Akses hanya dari jaringan sekolah.',
            errorCode: 'access.outside_network',
            httpStatus: 403,
            context: $context,
        );
    }

    /**
     * Error umum lainnya.
     */
    public static function generic(string $message, array $context = []): self
    {
        return new self(
            message: $message,
            errorCode: 'voting.error',
            httpStatus: 422,
            context: $context,
        );
    }
}
