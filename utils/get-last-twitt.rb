#!/usr/bin/env ruby

##
# 
# get-last-twitt.rb
#
# Retrieves last twitt sent by a specific @user and
# containing a specific #hashtag
#
# usage : get-last-twitt.rb @user #hashtag
# (arguments can come in any order)
#
# Deploying :
#
# adduser twitter
# su - twitter
# bash < <(curl -s https://raw.github.com/wayneeseguin/rvm/master/binscripts/rvm-installer)
# source ~/.bashrc
# apt-get install libreadline-dev
# rvm pkg install iconv
# rvm pkg install readline
# rvm install ruby-1.9.3
# rvm use 1.9.3@twitter --create --default
# bundle install
# 

require 'twitter'
#require 'memcached'
#require 'dalli'
require 'logger'
require 'json_builder'
require 'redis'

$logger = Logger.new(STDERR)
$logger.level = Logger::ERROR

user = nil
hashtag = nil

def log_and_exit(message)
  $logger.error message 
  $logger.close
  exit
end

log_and_exit "Error : you must pass a config file as argument" if (ARGV[0].nil?)
log_and_exit "Error : unable to open config file #{ARGV[0]}" unless (File.file? ARGV[0])

open(ARGV[0]).each do |line|
  line.chomp!
  $logger.debug("config : #{line}")
  begin
    if line.match('TWITTER_ACCOUNT') then
      user = line.match(".*'TWITTER_ACCOUNT'.*'(.*)'")[1];
      $logger.info("found a match for TWITTER_ACCOUNT : #{user}")
    end

    if line.match('TWITTER_HASHTAG') then
      hashtag = line.match(".*'TWITTER_HASHTAG'.*'(.*)'")[1];
      $logger.info("found a match for TWITTER_HASHTAG : #{hashtag}")
    end
  rescue
  end
end

log_and_exit "Error : unable to find hashtag and user in config file" if (user.nil? and hashtag.nil?)
log_and_exit "Error : user must be prefixed with '@'" unless (user.gsub('^@'))
log_and_exit "Error : hashtag must be prefixed with '#'" unless (hashtag.gsub('^#'))

# Twitter search doesn't want @ in front of users
user.gsub!('@','')

query = "from:#{user} #{hashtag}"

$logger.info "query : #{query}"

twitt = Twitter.search(query, :rpp => 1).first

if twitt.nil?
  $logger.warn "No twitt found"
  $logger.close
  exit
end

$logger.info "Found twitt : #{twitt.text}"

twitt.text.encode!('ISO-8859-1')
text = twitt.text

# dc = Dalli::Client.new('127.0.0.1:11211', :expires_in => 15*86400)
dc = Redis.new
dc.set 'SSO-LAST_NEWS', text.to_json
dc.expire('SSO-LAST_NEWS', 15*86400)

puts dc.get 'SSO-LAST_NEWS'

