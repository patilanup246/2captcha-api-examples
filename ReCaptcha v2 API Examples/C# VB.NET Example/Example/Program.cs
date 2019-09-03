using HtmlAgilityPack;
using System;
using System.Collections.Generic;
using System.IO;
using System.Net;
using System.Text.RegularExpressions;
using System.Web;
using TwoCaptcha_API;

namespace Example
{
    class Program
    {

        public static List<YouTube> _YouTubeList = new List<YouTube>(); 
        static void Main(string[] args)
        {

            string  channelurl = "https://www.youtube.com/user/schafer5";
            YouTube _YouTube =new YouTube();
            string responseData = string.Empty;
            string result;
            CookieContainer cookies = new CookieContainer();
            responseData = GetFirstRespParams(channelurl + "/about", ref cookies);
            HtmlAgilityPack.HtmlDocument htmlDocument = new HtmlAgilityPack.HtmlDocument();
            htmlDocument.LoadHtml(responseData);
            HtmlNodeCollection subscribercount = htmlDocument.DocumentNode.SelectNodes("//span[@class='yt-subscription-button-subscriber-count-branded-horizontal subscribed yt-uix-tooltip']");
            if(subscribercount!=null)
            {
                _YouTube.Subscribers = subscribercount[0].InnerText.Trim().Replace("subscribers", "");
            }
            HtmlNodeCollection Views = htmlDocument.DocumentNode.SelectNodes("//span[@class='about-stat']");
            if (Views != null)
            {
                foreach (var item in Views)
                {
                    if (item.InnerText.Trim().Contains("views")) {
                        _YouTube.Views = item.InnerText.Trim().Replace("views", "");
                        _YouTube.Views = _YouTube.Views.Trim().Replace("&bull; ", "");
                        break;
                    }
                }
            }
            HtmlNodeCollection Name = htmlDocument.DocumentNode.SelectNodes("//span[@class='qualified-channel-title-text']");
            if (Name != null)
            {
                foreach (var item in Name)
                {
                    if (item.InnerText.Trim() !=string.Empty)
                    {
                        _YouTube.Name = Name[0].InnerText.Trim();
                    }
                }
            }            
            _YouTube.ChannelUrl = channelurl;

            bool isemailbutton = false;
            HtmlNodeCollection EmailButton = htmlDocument.DocumentNode.SelectNodes("//paper-button[@class='style-scope ytd-button-renderer style-default size-default']");
            if (EmailButton != null)
            {
                foreach (var item in EmailButton)
                {
                    if (item.InnerText.Trim().Contains("View email address"))
                    {
                        isemailbutton = true;
                    }
                }
            }
            if (!isemailbutton)
            {
                Regex reg = new Regex(@"[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}", RegexOptions.IgnoreCase);
                Match match;
                List<string> results = new List<string>();
                for (match = reg.Match(responseData); match.Success; match = match.NextMatch())
                {
                    if (!(results.Contains(match.Value)))
                        _YouTube.Email = match.Value;
                }
            }
            else
            {
                int sta = responseData.IndexOf("XSRF_TOKEN") + 11;
                int end = responseData.IndexOf("XSRF_FIELD_NAME");
                string session = responseData.Substring(sta, end - sta).Replace(": \"", "");
                session = session.Replace("\",\n      '", "");
                sta = responseData.IndexOf("yt.setConfig('CHANNEL_ID',") + "yt.setConfig('CHANNEL_ID',".Length;
                end = responseData.IndexOf("yt.setConfig('CHANNEL_TAB',");
                string channeid = responseData.Substring(sta, end - sta).Replace("\");\n\n\n    ", "");
                channeid = channeid.Replace(" \"", "");
                string postData = String.Format("session_token=" + session);
                string HostName = "www.youtube.com";
                responseData = GetDataWithParams("https://www.youtube.com/channels_profile_ajax?action_get_business_email_captcha=1", ref cookies, postData, HostName);

                TwoCaptchaClient client = new TwoCaptchaClient("API Key");

                bool succeeded = client.SolveRecaptchaV2("6Lf39AMTAAAAALPbLZdcrWDa8Ygmgk_fmGmrlRog", "https://www.youtube.com/user/schafer5/about", "username:password@ip:port", ProxyType.HTTP, out result);

                postData = String.Format("channel_id=" + channeid + "&g-recaptcha-response=" + result + "&session_token=" + session);
                responseData = GetDataWithParams("https://www.youtube.com/channels_profile_ajax?action_verify_business_email_recaptcha=1", ref cookies, postData, HostName);

                sta = responseData.IndexOf("href=\"mailto:") + "href=\"mailto:".Length;
                end = responseData.IndexOf("\" target = ");
                string email = responseData.Substring(sta, end - sta).Replace("\");\n\n\n    ", "");
                email = email.Replace(" \"", "");
                _YouTube.Email = email;
            }
            _YouTubeList.Add(_YouTube);
        }
        public static bool AcceptAll(object sender, System.Security.Cryptography.X509Certificates.X509Certificate certification, System.Security.Cryptography.X509Certificates.X509Chain chain, System.Net.Security.SslPolicyErrors sslPolicyErrors)
        {
            return true;
        }
        public static void Towneauction_Post()
        {
            List<string> PageData = new List<string>();


        }

        public static string GetFirstRespParams(string URL, ref CookieContainer cookies)
        {
            //Access the page to extract view state information
            ServicePointManager.ServerCertificateValidationCallback = new System.Net.Security.RemoteCertificateValidationCallback(AcceptAll);
            HttpWebRequest webRequest = WebRequest.Create(URL) as HttpWebRequest;
            webRequest.CookieContainer = cookies;
            StreamReader responseReader = new StreamReader(webRequest.GetResponse().GetResponseStream());
            HttpWebResponse response = (HttpWebResponse)webRequest.GetResponse();
            response.Cookies = webRequest.CookieContainer.GetCookies(webRequest.RequestUri);
            string responseData = responseReader.ReadToEnd();
            responseReader.Close();
            webRequest.KeepAlive = false;
            return responseData;
        }
        public static string GetDataWithParams(string URL, ref CookieContainer cookies, string postData, string HostName)
        {
            //again access the login page with posted data to get cookies
            ServicePointManager.ServerCertificateValidationCallback = new System.Net.Security.RemoteCertificateValidationCallback(AcceptAll);
            HttpWebRequest webRequest = WebRequest.Create(URL) as HttpWebRequest;
            webRequest.Method = "POST";
            webRequest.Accept = "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8";
            webRequest.Headers.Add("Accept-Language", "en-us;q=0.7,en;q=0.3");
            webRequest.AutomaticDecompression = DecompressionMethods.GZip | DecompressionMethods.Deflate;
            webRequest.ContentType = "application/x-www-form-urlencoded";
            webRequest.CookieContainer = cookies;
            webRequest.Host = HostName;
            webRequest.Proxy = new WebProxy();
            webRequest.KeepAlive = true;
            webRequest.UserAgent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.93 Safari/537.36";

            //access the page with posted data
            StreamWriter requestWriter = new StreamWriter(webRequest.GetRequestStream());
            requestWriter.Write(postData);
            requestWriter.Close();
            StreamReader responseReader = new StreamReader(webRequest.GetResponse().GetResponseStream());
            string responseData = responseReader.ReadToEnd();
            responseReader.Close();
            webRequest.KeepAlive = false;
            return responseData;
        }
        private static string ExtractViewState(string s, string Name)
        {
            string viewStateNameDelimiter = Name;
            string valueDelimiter = "value=\"";

            int viewStateNamePosition = s.IndexOf(viewStateNameDelimiter);
            int viewStateValuePosition = s.IndexOf(valueDelimiter, viewStateNamePosition);

            int viewStateStartPosition = viewStateValuePosition + valueDelimiter.Length;
            int viewStateEndPosition = s.IndexOf("\"", viewStateStartPosition);

            return HttpUtility.UrlEncodeUnicode(s.Substring(viewStateStartPosition, viewStateEndPosition - viewStateStartPosition));
        }


    }
    public class YouTube
    {
        public string ChannelUrl { get; set; }
        public string Name { get; set; }
        public string Subscribers { get; set; }
        public string Views { get; set; }
        public string Email { get; set; }
        public string email_get_from { get; set; }
        
    }
}

