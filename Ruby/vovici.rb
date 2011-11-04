require 'rubygems'
require 'savon'

Savon.configure do |config|
  config.log = false            # disable logging
  #config.log_level = :info      # changing the log level
  #config.logger = Rails.logger  # using the Rails logger
end

client = Savon::Client.new do
  wsdl.document = "http://efm.infosurv.vovici.net/ws/projectdata.asmx?wsdl"
end
response = client.request :login do
  soap.body = { :user_name => '', :password => '' }
end
if response.success? == false
  puts "login failed"
  System.exit(0)
end

response = client.request :get_survey_list do
  soap.body = { :sharing_type => 2 }
end

puts response.to_hash[:get_survey_list_response][:get_survey_list_result][:projects][:project]

#response = client.request :get_column_list do
#  soap.body = { :project_id => '57203795' }
#end

#puts response.to_hash[:get_column_list_response][:get_column_list_result][:columns][:field]