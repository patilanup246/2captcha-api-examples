unit APIRuCaptcha;

interface

uses
  SysUtils, Forms, IdHTTP, IdMultipartFormData, StrUtils, DateUtils, EncdDecd,
  HttpSend, System.Classes, synautil;

function GetFormValue(Bound, Parametr, Value: string): string;

type
  TAPIRuCaptcha = class
  public
    class function GetBalance(const Key: string): string; //check balance

    class function UploadURL(const Key, url: string; var CaptchaRes:string): integer; //upload captcha image from URL
    // function returns captcha ID. Recognition result is placed to CaptchaRes variable

    class function SendReport(const Key, CaptchaId: string): string; // report incorrect result
  end;

const

  CRLF = #$0D#$0A; // \r\n

implementation

{ TAPIRuCaptcha }

class function TAPIRuCaptcha.GetBalance(const Key: string): string;
var
  URL: string;
  HTTP: TIdHTTP;
begin
  URL := Format('http://2captcha.com/res.php?key=%s&action=getbalance', [Key]);

  HTTP := TIdHTTP.Create(nil);
  try
    Result := HTTP.Get(URL);
  finally
    HTTP.Free;
  end;
end;

class function TAPIRuCaptcha.SendReport(const Key, CaptchaId: string): string;
var
  URL: string;
  HTTP: TIdHTTP;
begin
  URL := Format('http://2captcha.com/res.php?key=%s&action=reportbad&id=%s', [Key, CaptchaId]);

  HTTP := TIdHTTP.Create(nil);
  try
    Result := HTTP.Get(URL);
  finally
    HTTP.Free;
  end;
end;

class function TAPIRuCaptcha.UploadURL(const Key, url: string; var CaptchaRes:string): integer;
var
  Bound, response, CaptchaID: string;
  i: integer;
  Resp: TStringList;
  HTTP: THTTPSend;
  Image: TMemoryStream;
begin
  HTTP := THTTPSend.Create;
  if (HTTP.HTTPMethod('GET', url)) then
  begin
    Image := TMemoryStream.Create;
    Resp := TStringList.Create;
    Image.LoadFromStream(HTTP.Document);
    HTTP.Clear;

    Randomize;
    Bound := '-----' + IntToHex(random(65535), 8) + '_boundary';
    Resp.Text := GetFormValue(Bound, 'method', 'post');
    Resp.Text := Resp.Text + GetFormValue(Bound, 'key', Key);
    Resp.Text := Resp.Text + '--' + Bound + CRLF;
    Resp.Text := Resp.Text + 'Content-Disposition: form-data; name="file"; filename="image.jpg"' + CRLF + 'Content-Type: image/pjpeg' + CRLF + CRLF;
    WriteStrToStream(HTTP.Document, Resp.Text);
    HTTP.Document.CopyFrom(Image, 0);
    Resp.Text := CRLF + '--' + Bound + '--' + CRLF;
    WriteStrToStream(HTTP.Document, Resp.Text);
    HTTP.MimeType := 'multipart/form-data; boundary=' + Bound;

    if (HTTP.HTTPMethod('POST', 'http://2captcha.com/in.php')) then
    begin
      Resp.LoadFromStream(HTTP.Document);
      response := Resp.Strings[0];
      if Pos('ERROR', response) = 0 then
      begin
        if (Pos('OK|', response) > 0) then
          CaptchaID := StringReplace(response, 'OK|', '', [rfReplaceAll]);
        if (CaptchaID <> '') then
        begin
          Result := StrToInt(CaptchaID);
          for i := 0 to 20 do
          begin
            Sleep(3000);
              if (HTTP.HTTPMethod('GET', 'http://2captcha.com/res.php?key=' + Key + '&action=get&id=' + CaptchaID)) then
              begin
                Resp.LoadFromStream(HTTP.Document);
                response := Resp.Strings[0];
                if (Pos('ERROR_', response) > 0) then
                begin
                  CaptchaRes := response;
                  break;
                end;
                if (Pos('OK|', response) > 0) then
                begin
                  CaptchaRes := StringReplace(response, 'OK|', '', [rfReplaceAll]);
                  break;
                end;
              end;
            CaptchaRes := 'ERROR_TIMEOUT';
          end;
        end;
      end;
    end;
    Resp.Free;
    Image.Free;
  end;
  HTTP.Free;
end;

function GetFormValue(Bound, Parametr, Value: string): string;
begin
  Result := '--' + Bound + CRLF + 'Content-Disposition: form-data; name="' + Parametr + '"' + CRLF + CRLF + Value + CRLF;
end;

end.

