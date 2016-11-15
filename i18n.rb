require 'net/https'
require 'uri'
require 'json'
require 'fileutils'

TRANSLATION_PACKAGE = "magento"

def init
  response_body = api_call("https://support.zendesk.com/api/v2/locales/agent.json")

  locales = JSON.parse(response_body)["locales"]

  locale_url = "https://support.zendesk.com/api/v2/rosetta/locales/1.json?include=translations&packages=#{TRANSLATION_PACKAGE}"
  english = JSON.parse(api_call(locale_url))
  english_translations = english["locale"]["translations"]

  locales.each do |locale|
    locale_url = "#{locale["url"]}?include=translations&packages=#{TRANSLATION_PACKAGE}"
    translations = JSON.parse(api_call(locale_url))

    current_translation = []

    translations["locale"]["translations"].each { |key, value|
      current_translation << "\"#{english_translations[key]}\",\"#{value}\""
    }

    current_translation.sort!
    current_translation.unshift("\"English\",\"#{locale["name"]}\"")

    create_file(locale['locale'], current_translation)
  end
end

def api_call(url)
  uri = URI.parse(url)
  http = Net::HTTP.new(uri.host, uri.port)
  http.use_ssl = true
  http.verify_mode = OpenSSL::SSL::VERIFY_NONE

  request = Net::HTTP::Get.new(uri.request_uri)
  response = http.request(request)
  response.body
end

def create_file(locale, translations)
  locale = locale.gsub("-","_")
  if locale.length == 2
    locale = "#{locale}_#{locale.upcase}"
  else
    locale = locale.gsub(/_.+/){ |m| m.upcase }
  end

  puts "Created file for #{locale}."

  FileUtils.mkdir_p(locale) unless File.directory?(locale)
  f = File.new("./#{locale}/Zendesk_Zendesk.csv", "w+")
  translations.each{ |translation|
    f.puts(translation)
  }
  f.close
end

init()
