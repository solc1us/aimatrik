<?php

namespace App\Services\Penilaian;

use App\Models\DaftarPenilaianQuestion;
use App\Models\QuestionAnswerBidKapabilitas;
use App\Models\QuestionAnswerBidPraktik;
use App\Models\QuestionAnswerPraktik;
use Illuminate\Database\Eloquent\Collection;

class PenilaianQueryService
{
    public function getAllQuestion(string $daftar_penilaian_id, ?string $id_domain = null, ?string $id_bidang_kapabilitas = null): Collection
    {
        $query = DaftarPenilaianQuestion::query();
        $query->where('daftar_penilaian_id', $daftar_penilaian_id);
        if ($id_domain) {
            $query->where('domain_id', $id_domain);
        }

        $query->with([
            'domain',
            'questionAnswerBidKapabilitas' => function ($q) use ($id_bidang_kapabilitas) {
                if ($id_bidang_kapabilitas) {
                    $q->where('bidang_kapabilitas_id', $id_bidang_kapabilitas);
                }

                // Nested eager loading di sini
                $q->with([
                    'bidangKapabilitas',
                    'questionAnswerBidPraktik' => function ($q2) {
                        $q2->with([
                            'bidangPraktik',
                            'questionAnswerPraktik.praktik',
                        ]);
                    },
                ]);
            },
        ]);

        return $query->get();
    }

    public function getQuestionById(string $daftar_penilaian_id, ?string $id = null): ?DaftarPenilaianQuestion
    {
        $query = DaftarPenilaianQuestion::query();
        $query->where('daftar_penilaian_id', $daftar_penilaian_id);
        if (isset($id)) {
            $query->where('id', $id);
        }

        $query->orderBy('id', 'ASC');

        return $query->first();
    }

    public function getQuestionBidangKapabilitasById(string $daftarPeniaianId, ?string $id = null): ?QuestionAnswerBidKapabilitas
    {
        $query = QuestionAnswerBidKapabilitas::query();
        $query->where('daftar_penilaian_question_id', $daftarPeniaianId);
        if (isset($id)) {
            $query->where('id', $id);
        }

        $query->orderBy('id', 'ASC');

        return $query->first();
    }

    public function findBidangPraktikById(string $id, string $daftarPenilaianId): ?QuestionAnswerBidPraktik
    {
        $query = QuestionAnswerBidPraktik::query();
        $query->where('id', $id);
        $query->whereHas('questionAnswerBidKapabilitas.daftarPenilaianQuestion', function ($q) use ($daftarPenilaianId) {
            $q->where('daftar_penilaian_id', $daftarPenilaianId);
        });
        $query->orderBy('id', 'ASC');

        return $query->first();
    }

    public function findPraktikById(string $id, string $daftarPenilaianId): ?QuestionAnswerPraktik
    {
        $query = QuestionAnswerPraktik::query();
        $query->where('id', $id);
        $query->whereHas('questionAnswerBidPraktik.questionAnswerBidKapabilitas.daftarPenilaianQuestion', function ($q) use ($daftarPenilaianId) {
            $q->where('daftar_penilaian_id', $daftarPenilaianId);
        });
        $query->orderBy('id', 'ASC');

        return $query->first();
    }
}
