<?php

namespace App\Http\Controllers\Reptiles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Upload\ImageController;
use App\Models\Cage;
use App\Models\CageSerialCode;
use App\Models\TemperatureHumidity;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class CageController extends Controller
{
    // 사육장 목록
    public function index()
    {
        $user = JWTAuth::user();

        try {
            $cages = $user->cages;

            return response()->json([
                'msg'   => '성공',
                'cages' => $cages->isEmpty() ? '데이터 없음' : $cages,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'msg'   => '서버 오류',
                'error' => $e->getMessage(),
            ], 500);
        }
        
    }

    // 사육장 등록
    public function store(Request $request)
    {
        $validatedList = [
            'reptileSerialCode' => ['nullable', 'string'],
            'memo'              => ['nullable', 'string'],
            'setTemp'           => ['required'],
            'setHum'            => ['required'],
            'serialCode'        => ['required', 'string'],
        ];
        if($request->hasFile('images')){
            $validatedList['images'] = ['nullable', 'array'];
            $validatedList['images.*'] = ['image', 'mimes:jpg,jpeg,png,bmp,gif,svg,webp', 'max:2048'];
        }

        // dd($request->all());

        $validator = Validator::make($request->all(), $validatedList);

        if($validator->fails()){
            return response()->json([
                'msg'   => '유효성 검사 오류',
                'error' => $validator->errors()->all(),
            ], 400);
        }
        
        $reqData = $validator->safe();

        try {
            $msg = '';
            $state = 201;

            // 파충류 등록 유무 확인
            if($reqData['reptileSerialCode'] !== null){
                $cageConfirm = Cage::where('reptile_serial_code', $reqData['reptileSerialCode'])->first();
                if(!empty($cageConfirm) && $cageConfirm->expired_at === null){ 
                    $msg = '이미 등록된 파충류';
                    $state = 400;
                }
            }

            $serialCodeConfirm = CageSerialCode::where('serial_code', $reqData['serialCode'])->first();
            // 일련번호 확인
            if(empty($serialCodeConfirm)){
                $msg = '일련번호를 찾을 수 없음';
                $state = 400;

            } else{
                $user = JWTAuth::user();
                $createList = [
                    'user_id'             => $user->id,
                    'reptile_serial_code' => $reqData['reptileSerialCode'],
                    'memo'                => $reqData['memo'],
                    'set_temp'            => $reqData['setTemp'],
                    'set_hum'             => $reqData['setHum'],
                    'serial_code'         => $reqData['serialCode'],
                    'img_urls'            => null,
                ];

                if($reqData->has('images')){
                    $images = new ImageController();
                    $imgUrls = $images->uploadImageForController($reqData['images'], 'cages');
                    $createList['img_urls'] = $imgUrls;
                }

                Cage::create($createList);

                $msg = '등록 완료';
            }

            return response()->json([
                'msg' => $msg,
            ], $state);

        } catch (Exception $e) {
            return response()->json([
                'msg'   => '서버 오류',
                'error' => $e->getMessage()
            ]);
        }

    }

    // 사육장 정보
    public function show(Cage $cage)
    {
        $user = JWTAuth::user();

        if($cage->user_id !== $user->id){
            return response()->json([
                'msg' => '권한 없음'
            ], 403);
        } 

        return response()->json([
            'msg' => '성공',
            'reptile' => $cage
        ], 200);
    }

    // 사육장 정보 수정
    public function update(Request $request, Cage $cage)
    {
        $validatedList = [
            'reptileSerialCode' => ['nullable', 'string'],
            'memo'              => ['nullable', 'string'],
            'setTemp'           => ['required'],
            'setHum'            => ['required'],
            'serialCode'        => ['required', 'string'],
            'imgUrls'           => ['nullable', 'array'],
        ];
        if($request->hasFile('images')){
            $validatedList['newImages'] = ['nullable', 'array'];
            $validatedList['newImages.*'] = ['image', 'mimes:jpg,jpeg,png,bmp,gif,svg,webp', 'max:2048'];
        }

        $validator = Validator::make($request->all(), $validatedList);

        if($validator->fails()){
            return response()->json([
                'msg'   => '유효성 검사 오류',
                'error' => $validator->errors()->all(),
            ], 400);
        }

        $validator = Validator::make($request->all(), $validatedList);

        if($validator->fails()){
            return response()->json([
                'msg'   => '유효성 검사 오류',
                'error' => $validator->errors()->all(),
            ], 400);
        }
        
        $reqData = $validator->safe();

        $user = JWTAuth::user();

        $dbImgList = $cage->img_urls;
        $updateImgList = $reqData['imgUrls'];
        $deleteImgList = array_diff($dbImgList, $updateImgList);

        $images = new ImageController();
        $deleteResult = $images->deleteImages($deleteImgList);

        if(gettype($deleteResult) !== 'boolean'){
            return response()->json([
                'msg' => '이미지 삭제 실패',
                'error' => $deleteResult
            ], 500);
        }

        if($reqData->has('newImages')){
            $imgUrls = $images->uploadImageForController($reqData['newImages'], 'cages');
            $uploadImgList = array_merge($updateImgList, $imgUrls);
        } else{
            $uploadImgList = $updateImgList;
        }

        if($cage->user_id !== $user->id){
            return response()->json([
                'msg' => '수정 권한 없음'
            ], 403);
        }

        try {

            if($reqData['images'])
            
            $cage->update([
                'reptile_serial_code' => $reqData['reptileSerialCode'],
                'memo'                => $reqData['memo'],
                'set_temp'            => $reqData['setTemp'],
                'set_hum'             => $reqData['setHum'],
                'img_urls'            => $uploadImgList,
            ]);

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

    // 사육장 정보 삭제
    public function destroy(Cage $cage)
    {
        $user = JWTAuth::user();

        if($cage->user_id !== $user->id){
            return response()->json([
                'msg' => '권한 없음'
            ], 403);
        }

        try {
            $cage->delete();

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

    // 온습도 데이터 전달(프론트에서 사용)
    public function getTempHumData(Cage $cage)
    {
        $user = JWTAuth::user();

        if($cage->user_id !== $user->id){
            return response()->json([
                'msg' => '권한 없음'
            ], 403);
        }

        $serialCode = $cage->serial_code;

        try {
            $tempHumData = TemperatureHumidity::where('serial_code', $serialCode)->get();

            return response()->json([
                'msg' => '성공',
                'data' => $tempHumData
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'msg' => '서버 오류',
                'error' => $e->getMessage()
            ], 500);
        }


    }
}