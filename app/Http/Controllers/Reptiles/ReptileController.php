<?php

namespace App\Http\Controllers\Reptiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Upload\ImageController;
use App\Http\Requests\ReptileRequest;
use App\Models\Reptile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
class ReptileController extends Controller
{
    // 파충류 목록
    public function index()
    {
        $user = JWTAuth::user();

        try {
            $reptiles = $user->reptiles;

            if($reptiles->isEmpty()){
                return response()->json([
                    'msg' => '데이터 없음'
                ], 200);
            }

            return response()->json([
                'msg'      => '성공',
                'reptiles' => $reptiles
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'msg'   => '서버 오류',
                'error' => $e->getMessage()
            ], 500);
        }

        
    }

    // 파충류 등록
    public function store(ReptileRequest $request)
    {
        try {
            $request->validated();

        } catch (ValidationException $e) {
            return response()->json([
                'msg'              => '유효성 검사 오류',
                'validation error' => $e->getMessage()
            ], 400);
        }

        $user = JWTAuth::user();
        $validator = $request->safe();
        
        try {
            $serialCode = 'REPTILE-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(2));
            while(Reptile::where('serial_code', $serialCode)->exists()){
                $serialCode = 'REPTILE-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(2));
            }

            $images = new ImageController();
            $imageUrls = $images->uploadImageForController($validator['images'], 'reptiles');

            Reptile::create([
                'user_id'       => $user->id,
                'serial_code'   => $serialCode,
                'nickname'      => $validator['nickname'],
                'species'       => $validator['species'],
                'gender'        => $validator['gender'],
                'birth'         => $validator['birth'],
                'memo'          => $validator['memo'],
                'img_urls'      => $imageUrls,
            ]);

            return response()->json([
                'msg' => '등록 완료',
            ], 201);
            
        } catch (Exception $e) {
            return response()->json([
                'msg' => '서버 오류',
                'error' => $e->getMessage()
            ], 500);
        }


    }

    // 파충류 정보 확인
    public function show(Reptile $reptile)
    {
        $user = JWTAuth::user();

        if($reptile->user_id !== $user->id){
            return response()->json([
                'msg' => '권한 없음'
            ], 403);
        }

        return response()->json([
            'msg' => '성공',
            'reptile' => $reptile
        ], 200);
        
    }


    // 파충류 정보 수정
    public function update(Request $request, Reptile $reptile)
    {
        $user = JWTAuth::user();

        if($reptile->user_id !== $user->id){
            return response()->json([
                'msg' => '권한 없음'
            ], 403);
        }

        try {
            $reptile->update($request->all());

            return response()->json([
                'msg' => '수정 완료'
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'msg' => '서버 오류',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 파충류 정보 삭제
    public function destroy(Reptile $reptile)
    {
        $user = JWTAuth::user();

        if($reptile->user_id !== $user->id){
            return response()->json([
                'msg' => '권한 없음'
            ], 403);
        }

        try {
            $reptile->delete();

            return response()->json([
                'msg' => '삭제 완료'
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'msg' => '서버 오류',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
