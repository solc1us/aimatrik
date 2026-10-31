<?php

namespace App\Http\Controllers\Admin;

use App\Constants\AppConst;
use App\Http\Controllers\Controller;
use App\Models\DaftarPenilaian;
use App\Services\DaftarPenilaian\DaftarPenilaianQueryService;
use App\Services\Penilaian\PenilaianQueryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

final class RekapanPenilaianController extends Controller
{
    // Constructor
    public function __construct(
        private readonly DaftarPenilaianQueryService $daftarPenilaianQueryService,
        private readonly PenilaianQueryService $penilaianQueryService,
    ) {}

    public function index(Request $request, string $id_daftar_penilaian): View
    {
        // Get daftar penilaian
        $findDaftarPenilaian = $this->daftarPenilaianQueryService->findDaftarPenilaianById(id: $id_daftar_penilaian);

        // Cek apakah daftar penilaian ada
        if (! isset($findDaftarPenilaian)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        // Cek apakah daftar penilaian aktif dan publish
        if (! $findDaftarPenilaian->is_active || ! $findDaftarPenilaian->is_publish) {
            abort(Response::HTTP_NOT_FOUND);
        }

        // Get all question
        $getAllQuestion = $this->penilaianQueryService->getAllQuestion(daftar_penilaian_id: $findDaftarPenilaian->id);

        // Generate rekap penilaian
        $hitungRekapPenilaian = $this->_generateRekapPenilaian(getAllQuestion: $getAllQuestion, groupByDomain: true);

        // Mapping rekap penilaian
        $dataRingkasan = [];
        foreach ($hitungRekapPenilaian as $indexDomain => $ringkasanPerDomain) {
            $collection = collect($ringkasanPerDomain);

            // Mendapatkan nilai minimum dengan mengabaikan null
            $minLevelKematangan = $this->_calculateMinimumLevel($collection, 'levelSaatIni');

            // Mendapatkan nilai minimum dengan mengabaikan null
            $minLevelTarget = $this->_calculateMinimumLevel($collection, 'levelTarget');

            // Mendapatkan pertanyaan berdasarkan domain dan index domain
            $domainQuestion = $this->penilaianQueryService->getQuestionById(
                daftar_penilaian_id: $findDaftarPenilaian->id,
                id: $indexDomain
            );

            // Push tu array
            $dataRingkasan[$indexDomain] = [
                'domain' => $domainQuestion->domain?->name,
                'levelSaatIni' => is_null($minLevelKematangan) ? null : (float) $minLevelKematangan,
                'levelTarget' => is_null($minLevelTarget) ? null : (float) $minLevelTarget,
            ];
        }
        // Mendapatkan nilai minimum secara umum untuk seluruh data
        $minLevelAllKematangan = collect($dataRingkasan)->min('levelSaatIni');
        $minLevelAllTarget = collect($dataRingkasan)->min('levelTarget');

        // Menampilkan halaman
        return view('admin.pages.rekapan-penilaian.index', compact(
            'findDaftarPenilaian',
            'dataRingkasan',
            'minLevelAllKematangan',
            'minLevelAllTarget',
        ));
    }

    public function detail(Request $request, string $id_daftar_penilaian): View
    {
        // Get daftar penilaian
        $findDaftarPenilaian = $this->daftarPenilaianQueryService->findDaftarPenilaianById(id: $id_daftar_penilaian);

        // Cek apakah daftar penilaian ada
        if (! isset($findDaftarPenilaian)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        // Cek apakah daftar penilaian aktif dan publish
        if (! $findDaftarPenilaian->is_active || ! $findDaftarPenilaian->is_publish) {
            abort(Response::HTTP_NOT_FOUND);
        }

        // Mendapatkan seluruh data pertanyaan
        $getAllDomainQuestion = $this->penilaianQueryService->getAllQuestion(daftar_penilaian_id: $findDaftarPenilaian->id);

        // Mengecek apakah ada request page dan mendefinisikan domain yang akan ditampilkan ke user
        if (isset($request->page)) {
            $getQuestionById = $this->penilaianQueryService->getQuestionById(daftar_penilaian_id: $findDaftarPenilaian->id, id: $request->page);
        } else {
            $getQuestionById = $this->penilaianQueryService->getQuestionById(daftar_penilaian_id: $findDaftarPenilaian->id);
        }

        // Cek apakah ada atau tidak
        if (! isset($getQuestionById)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        // Mendapatkan seluruh data pertanyaan berdasarkan domain dan bidang kapabilitas yang didefinisikan
        $getAllQuestion = $this->penilaianQueryService->getAllQuestion(daftar_penilaian_id: $findDaftarPenilaian->id, id_domain: $getQuestionById->domain_id);

        // Menghitugn rekapan penilaian
        $hitungRekapPenilaian = $this->_generateRekapPenilaian(getAllQuestion: $getAllQuestion, groupByDomain: false);
        $mergedRowspan = collect($hitungRekapPenilaian)
            ->groupBy('idBidangKapabilitas')
            ->map(function ($items, $idBid) {
                $result = [
                    'total' => $items->count(),
                    'level' => $items->groupBy('levelKematangan')->map->count(),
                ];

                return $result;
            });

        // Menampilkan halaman
        return view('admin.pages.rekapan-penilaian.detail', compact(
            'getAllQuestion',
            'getAllDomainQuestion',
            'findDaftarPenilaian',
            'getQuestionById',
            'hitungRekapPenilaian',
            'mergedRowspan',
        ));
    }

    public function print(Request $request, string $id_daftar_penilaian): View
    {
        // Get daftar penilaian
        $findDaftarPenilaian = $this->daftarPenilaianQueryService->findDaftarPenilaianById(id: $id_daftar_penilaian);

        // Cek apakah daftar penilaian ada
        if (! isset($findDaftarPenilaian)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        // Cek apakah daftar penilaian aktif dan publish
        if (! $findDaftarPenilaian->is_active || ! $findDaftarPenilaian->is_publish) {
            abort(Response::HTTP_NOT_FOUND);
        }

        // Mendapatkan seluruh data pertanyaan
        $getAllQuestion = $this->penilaianQueryService->getAllQuestion(daftar_penilaian_id: $findDaftarPenilaian->id);

        // Menghitugn rekapan penilaian
        $hitungRekapPenilaian = $this->_generateRekapPenilaian(getAllQuestion: $getAllQuestion, groupByDomain: true);
        $getAllAnswer = $this->_generateAnswer(getAllQuestion: $getAllQuestion);

        // Mapping
        $dataRingkasan = $this->_mappingRingkasanPrint(hitungRekapPenilaian: $hitungRekapPenilaian, findDaftarPenilaian: $findDaftarPenilaian);

        // Mendapatkan nilai minimum secara umum
        $minLevelAllKematangan = collect($dataRingkasan)->min('levelSaatIni');
        $minLevelAllTarget = collect($dataRingkasan)->min('levelTarget');

        // Menampilkan halaman
        return view('admin.pages.rekapan-penilaian.print', compact(
            'findDaftarPenilaian',
            'dataRingkasan',
            'getAllAnswer',
            'minLevelAllKematangan',
            'minLevelAllTarget',
        ));
    }

    // Private function here
    private function _mappingRingkasanPrint(array $hitungRekapPenilaian, DaftarPenilaian $findDaftarPenilaian): array
    {
        $dataRingkasan = [];
        foreach ($hitungRekapPenilaian as $indexDomain => $ringkasanPerDomain) {
            $collection = collect($ringkasanPerDomain);

            // Mendapatkan nilai minimum untuk levelSaatIni dan levelTarget

            $minLevelKematangan = $this->_calculateMinimumLevel($collection, 'levelSaatIni');

            $minLevelTarget = $this->_calculateMinimumLevel($collection, 'levelTarget');

            // Mendapatkan domain berdasarkan id domain
            $domainQuestion = $this->penilaianQueryService->getQuestionById(
                daftar_penilaian_id: $findDaftarPenilaian->id,
                id: $indexDomain
            );

            // Mengelompokkan data berdasarkan idBidangKapabilitas dan memastikan setiap item terstruktur dengan benar
            $ringkasanPerDomain = collect($ringkasanPerDomain)
                ->groupBy('idBidangKapabilitas') // Mengelompokkan berdasarkan idBidangKapabilitas
                ->map(function ($group) {
                    // Menghitung minLevelKematangan per Bidang Kapabilitas
                    $minLevelKematanganPerBidangKapabilitas = $this->_calculateMinimumLevel($group, 'levelSaatIni');

                    return $group->map(function ($item) use ($minLevelKematanganPerBidangKapabilitas) {
                        // Pastikan setiap item memiliki data yang diperlukan dan terstruktur dengan baik
                        return [
                            'idBidangKapabilitas' => $item['idBidangKapabilitas'],
                            'namaBidangKapabilitas' => $item['namaBidangKapabilitas'],
                            'namaBidangPraktik' => $item['namaBidangPraktik'],
                            'levelSaatIni' => $item['levelSaatIni'],
                            'levelTarget' => $item['levelTarget'],
                            'celah' => (! is_null($item['levelTarget']) && ! is_null($item['levelSaatIni']))
                                ? (float) $item['levelTarget'] - $item['levelSaatIni']
                                : null,
                            'performa' => $item['performa'],
                            'rekomendasi' => $item['rekomendasi'],
                            'levelKematangan' => $minLevelKematanganPerBidangKapabilitas, // Menambahkan nilai level kematangan per bidang kapabilitas
                        ];
                    });
                })
                ->flatten(1) // Menggabungkan semua grup ke dalam satu array
                ->toArray();

            // Mapping untuk data ringkasan
            $dataRingkasan[$indexDomain] = [
                'domain' => $domainQuestion->domain?->name,
                'ringkasanPerDomain' => $ringkasanPerDomain, // Ini sudah terstruktur dengan benar
                'levelSaatIni' => is_null($minLevelKematangan) ? null : (float) $minLevelKematangan,
                'levelTarget' => is_null($minLevelTarget) ? null : (float) $minLevelTarget,
            ];
        }

        return $dataRingkasan;
    }

    private function _generateAnswer(Collection $getAllQuestion): array
    {
        $result = [];

        $sortedQuestions = $getAllQuestion->sortBy('id');

        foreach ($sortedQuestions as $itemQuestion) {
            foreach ($itemQuestion->questionAnswerBidKapabilitas->sortBy('id') as $itemQuestionAnswerBidKapabilitas) {
                foreach ($itemQuestionAnswerBidKapabilitas->questionAnswerBidPraktik->sortBy('id') as $itemQuestionAnswerBidPraktik) {

                    $groupedItems = $itemQuestionAnswerBidPraktik->questionAnswerPraktik
                        ->sortBy('id')
                        ->groupBy(fn ($item) => $item->praktik?->kategori_level)
                        ->sortKeys();

                    $answerByLevel = [];
                    $levelSaatIni = $this->_findLevelSaatIni(groupedItems: $groupedItems);

                    foreach ($groupedItems as $level => $items) {
                        $values = [];
                        $answers = [];
                        foreach ($items as $item) {
                            $values[] = is_null($item->answer) ? null : AppConst::NPLF_VAL[$item->answer];
                            $answers[] = [
                                'domainId' => $item->questionAnswerBidPraktik?->questionAnswerBidKapabilitas?->daftar_penilaian_question_id,
                                'domain' => $item->questionAnswerBidPraktik?->questionAnswerBidKapabilitas?->daftarPenilaianQuestion?->domain->name,
                                'bidangKapabilitas' => $item->questionAnswerBidPraktik?->questionAnswerBidKapabilitas?->bidangKapabilitas->name,
                                'bidangPraktik' => $item->questionAnswerBidPraktik?->bidangPraktik?->name,
                                'question' => $item->praktik?->praktik,
                                'answer' => is_null($item->answer) ? null : AppConst::NPLF_ARR[$item->answer],
                                'levelTarget' => $item->questionAnswerBidPraktik?->level_target,
                            ];
                        }

                        // Di sini kita cek values
                        $allNull = collect($values)->every(fn ($val) => is_null($val));

                        $rerata = (float) array_sum($values) / count($values);
                        $achievementScore = (float) ($rerata / 3) * 100;

                        $kategori = null;
                        foreach (AppConst::NPLF_MAKS_MIN as $kode => $range) {
                            if ($achievementScore >= $range['min'] && $achievementScore <= $range['max']) {
                                $kategori = array_search($kode, AppConst::NPLF_VAL, true);
                                break;
                            }
                        }
                        $keterangan = AppConst::NPLF_ARR[$kategori] ?? null;

                        $answers = array_map(function ($answer) use ($level, $levelSaatIni, $rerata, $achievementScore, $keterangan, $kategori) {
                            return array_merge($answer, [
                                'level' => $level,
                                'levelSaatIni' => $levelSaatIni,
                                'rerata' => (float) $rerata,
                                'achievement' => number_format((float) $achievementScore, 2, '.', ''),
                                'keterangan' => $keterangan,
                                'catatan' => $kategori,
                            ]);
                        }, $answers);

                        $answerByLevel[$level] = $answers;
                    }

                    // Gabungkan semua data dalam answerByLevel menjadi satu array datar
                    $answerByLevel = array_merge(...array_values($answerByLevel));

                    $result[$itemQuestion->id] = array_merge($result[$itemQuestion->id] ?? [], $answerByLevel);
                }
            }
        }

        return $result;
    }

    private function _generateRekapPenilaian(Collection $getAllQuestion, bool $groupByDomain = false): array
    {
        // Array yang akan direturn
        $arrDataHasilPengecekan = [];

        // Menghitung rekapan penilaian di sort by id
        foreach ($getAllQuestion->sortBy('id')->values() as $itemQuestion) {
            $domainQuestion = $itemQuestion->id;

            // Looping bidang kapabilitas sort by id
            foreach ($itemQuestion->questionAnswerBidKapabilitas->sortBy('id')->values() as $itemQuestionAnswerBidKapabilitas) {
                $levelSaatIniArr = [];
                $dataBidangPraktikTemp = [];

                // Looping bidang praktik sort by id
                foreach (
                    $itemQuestionAnswerBidKapabilitas->questionAnswerBidPraktik->sortBy('id')->values() as $itemQuestionAnswerBidPraktik
                ) {
                    // Grouping bidang praktik berdasarkan kategori level
                    $groupedByLevel = $itemQuestionAnswerBidPraktik->questionAnswerPraktik
                        ->groupBy(fn ($item) => $item->praktik?->kategori_level)
                        ->map(fn ($group) => $group->sortBy('id')->values())
                        ->sortKeys();

                    // Definisikan level saat ini dan rekomendasi default
                    $levelSaatIni = $this->_findLevelSaatIni(groupedItems: $groupedByLevel);

                    // Level target yang didapat
                    $levelTarget = $itemQuestionAnswerBidPraktik->level_target ?? null;

                    // Daftar Rekomendasi
                    $arrRekomendasi = $this->_getRekomendasi(groupedItems: $groupedByLevel, levelTarget: $levelTarget, levelSaatIni: $levelSaatIni);

                    // Simpan level saat ini
                    $levelSaatIniArr[] = $levelSaatIni;

                    // Format rekomendasi sebagai list
                    $rekomendasiHTML = '';
                    if (count($arrRekomendasi) > 0) {
                        $rekomendasiHTML .= '<ol>';
                        foreach ($arrRekomendasi as $daftarRekomendasi) {
                            $rekomendasiHTML .= '<li>'.$daftarRekomendasi.'</li>';
                        }
                        $rekomendasiHTML .= '</ol>';
                    }

                    // Simpan data untuk nanti di-inject levelKematangan
                    $dataBidangPraktikTemp[] = [
                        'data' => [
                            'idBidangKapabilitas' => $itemQuestionAnswerBidPraktik->question_answer_bid_kapabilitas_id,
                            'namaBidangKapabilitas' => $itemQuestionAnswerBidPraktik->questionAnswerBidKapabilitas?->bidangKapabilitas?->code.'-'.$itemQuestionAnswerBidPraktik->questionAnswerBidKapabilitas?->bidangKapabilitas?->name,
                            'namaBidangPraktik' => $itemQuestionAnswerBidPraktik->bidangPraktik?->code.'-'.$itemQuestionAnswerBidPraktik->bidangPraktik?->name,
                            'levelTarget' => is_null($levelTarget) ? null : (float) $levelTarget,
                            'levelSaatIni' => is_null($levelSaatIni) ? null : (float) $levelSaatIni,
                            'celah' => (! is_null($levelTarget) && ! is_null($levelSaatIni))
                                ? (float) $levelTarget - $levelSaatIni
                                : null,
                            'performa' => (! is_null($levelTarget) && ! is_null($levelSaatIni))
                                ? ($levelTarget > $levelSaatIni ? 'Buruk' : 'Baik')
                                : null,
                            'rekomendasi' => $rekomendasiHTML,
                            'levelKematangan' => null, // akan diisi setelah semua praktik diproses
                        ],
                        'domain' => $domainQuestion,
                    ];
                }

                // Hitung level kematangan (nilai paling kecil dari levelSaatIni)
                $filteredLevelSaatIni = array_filter($levelSaatIniArr, fn ($val) => ! is_null($val));
                $levelKematangan = count($filteredLevelSaatIni) > 0 ? (float) min($filteredLevelSaatIni) : null;

                // Inject level kematangan ke semua hasil bidang praktik
                foreach ($dataBidangPraktikTemp as $itemTemp) {
                    $data = $itemTemp['data'];
                    $data['levelKematangan'] = $levelKematangan;

                    if ($groupByDomain) {
                        $arrDataHasilPengecekan[$itemTemp['domain']][] = $data;
                    } else {
                        $arrDataHasilPengecekan[] = $data;
                    }
                }
            }
        }

        // Return hasil
        return $arrDataHasilPengecekan;
    }

    private function _findLevelSaatIni(Collection $groupedItems): ?int
    {
        $levelSaatIni = null;
        // Level Saat Ini
        foreach ($groupedItems as $level => $itemsGrouped) {
            $answers = $itemsGrouped->pluck('answer');

            if ($answers->every(fn ($val) => is_null($val))) {
                break;
            }

            if ($answers->contains(fn ($val) => $val !== 'F' && ! is_null($val))) {
                $levelSaatIni = (int) $level - 1;
                break;
            }

            $levelSaatIni = (int) $level;
        }

        return $levelSaatIni;
    }

    private function _getRekomendasi(Collection $groupedItems, ?int $levelTarget = null, ?int $levelSaatIni = null): array
    {
        $arrRekomendasi = [];
        // Kumpulkan rekomendasi dari semua jawaban yang bukan 'F'
        foreach ($groupedItems as $level => $itemsGrouped) {
            foreach ($itemsGrouped as $item) {
                if ($item->answer !== 'F' && $levelSaatIni !== null && (int) $level < (int) $levelTarget + 1) {
                    $rekomendasi = $item->praktik?->praktik;
                    if ($rekomendasi) {
                        $arrRekomendasi[] = $rekomendasi;
                    }
                }
            }
        }

        return $arrRekomendasi;
    }

    private function _calculateMinimumLevel(Collection $collection, string $key): ?float
    {
        $filtered = $collection->pluck($key)->filter(fn ($value) => ! is_null($value));

        return $filtered->isEmpty() ? null : (float) $filtered->min();
    }
}
